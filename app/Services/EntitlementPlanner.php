<?php

namespace App\Services;

use App\Enums\CycleStatus;
use App\Models\AssistanceCycle;
use App\Models\EntitlementRule;
use App\Models\Household;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Feature 2 – Assistance cycle and entitlement planning.
 *
 * Computes entitlements from the cycle's rules, previews them, and on
 * activation materialises them into the `entitlements` table while locking
 * the rules.
 */
class EntitlementPlanner
{
    /**
     * Compute the entitlement rows for one household from a set of rules.
     *
     * @param  Collection<int, EntitlementRule>  $rules
     * @return array<int, array{item_id:int, item:Item, quantity:float, rule:EntitlementRule}>
     */
    public function computeForHousehold(Household $household, Collection $rules): array
    {
        $rows = [];
        foreach ($rules as $rule) {
            if (! $rule->appliesTo($household->household_size)) {
                continue;
            }
            $qty = $rule->quantityFor($household->household_size);
            if ($qty <= 0) {
                continue;
            }
            // Non-overlapping bands are enforced at rule creation; if two rules
            // still match the same item we keep the more specific (higher min) one.
            if (isset($rows[$rule->item_id]) && $rows[$rule->item_id]['rule']->min_household_size >= $rule->min_household_size) {
                continue;
            }
            $rows[$rule->item_id] = [
                'item_id' => $rule->item_id,
                'item' => $rule->item,
                'quantity' => $qty,
                'rule' => $rule,
            ];
        }

        return array_values($rows);
    }

    /**
     * Preview the entitlements the cycle would generate right now.
     *
     * @return array{rows: Collection<int, array>, totals: Collection<int, array>, household_count:int, households_without_entitlement:int}
     */
    public function preview(AssistanceCycle $cycle): array
    {
        $rules = $cycle->rules()->with('item')->get();
        $households = $cycle->eligibleHouseholdsQuery()->get();

        $rows = collect();
        $without = 0;
        foreach ($households as $household) {
            $computed = $this->computeForHousehold($household, $rules);
            if (! $computed) {
                $without++;
            }
            foreach ($computed as $row) {
                $rows->push([
                    'household' => $household,
                    'item' => $row['item'],
                    'quantity' => $row['quantity'],
                    'rule' => $row['rule'],
                ]);
            }
        }

        $totals = $rows->groupBy(fn ($r) => $r['item']->id)->map(fn (Collection $group) => [
            'item' => $group->first()['item'],
            'households' => $group->count(),
            'quantity' => round($group->sum('quantity'), 2),
        ])->values();

        return [
            'rows' => $rows,
            'totals' => $totals,
            'household_count' => $households->count(),
            'households_without_entitlement' => $without,
        ];
    }

    /**
     * Validate that a (new or updated) rule's size band does not overlap an
     * existing band for the same item in the same cycle.
     *
     * @param  array{item_id:int, min_household_size:int, max_household_size:int|null}  $data
     */
    public function assertNoOverlap(AssistanceCycle $cycle, array $data, ?EntitlementRule $ignore = null): void
    {
        $min = (int) $data['min_household_size'];
        $max = $data['max_household_size'] !== null && $data['max_household_size'] !== '' ? (int) $data['max_household_size'] : PHP_INT_MAX;

        $others = $cycle->rules()->where('item_id', $data['item_id'])
            ->when($ignore, fn ($q) => $q->where('id', '!=', $ignore->id))
            ->get();

        foreach ($others as $other) {
            $oMin = $other->min_household_size;
            $oMax = $other->max_household_size ?? PHP_INT_MAX;
            if ($min <= $oMax && $oMin <= $max) {
                throw ValidationException::withMessages([
                    'min_household_size' => "This size band overlaps the existing band {$other->sizeBandLabel()} for the same item.",
                ]);
            }
        }
    }

    /**
     * Activate the cycle: lock the rules and materialise entitlements.
     */
    public function activate(AssistanceCycle $cycle, User $approver): AssistanceCycle
    {
        if (! $cycle->isDraft()) {
            throw ValidationException::withMessages(['status' => 'Only draft cycles can be activated.']);
        }
        if ($cycle->rules()->doesntExist()) {
            throw ValidationException::withMessages(['rules' => 'Add at least one entitlement rule before activating the cycle.']);
        }

        $preview = $this->preview($cycle);
        if ($preview['rows']->isEmpty()) {
            throw ValidationException::withMessages(['rules' => 'The current rules do not produce any entitlement for the eligible households.']);
        }

        return DB::transaction(function () use ($cycle, $approver, $preview) {
            $cycle->entitlements()->delete();

            $now = now();
            $inserts = $preview['rows']->map(fn ($r) => [
                'assistance_cycle_id' => $cycle->id,
                'household_id' => $r['household']->id,
                'item_id' => $r['item']->id,
                'quantity' => $r['quantity'],
                'household_size_at_activation' => $r['household']->household_size,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            foreach (array_chunk($inserts, 500) as $chunk) {
                $cycle->entitlements()->insert($chunk);
            }

            $cycle->status = CycleStatus::Active;
            $cycle->activated_by = $approver->id;
            $cycle->activated_at = $now;
            $cycle->eligible_household_count = $preview['household_count'];
            $cycle->save();

            return $cycle;
        });
    }

    public function close(AssistanceCycle $cycle, User $actor): AssistanceCycle
    {
        if (! $cycle->isActive()) {
            throw ValidationException::withMessages(['status' => 'Only active cycles can be closed.']);
        }
        $cycle->status = CycleStatus::Closed;
        $cycle->closed_by = $actor->id;
        $cycle->closed_at = now();
        $cycle->save();

        return $cycle;
    }

    /** Summary of materialised entitlements per item (for active/closed cycles). */
    public function materialisedTotals(AssistanceCycle $cycle): Collection
    {
        return $cycle->entitlements()
            ->selectRaw('item_id, COUNT(*) as households, SUM(quantity) as quantity')
            ->groupBy('item_id')
            ->with('item')
            ->get();
    }
}
