<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assistance\EntitlementRuleRequest;
use App\Models\AssistanceCycle;
use App\Models\EntitlementRule;
use App\Services\EntitlementPlanner;
use Illuminate\Http\RedirectResponse;

/**
 * Feature 2 – Entitlement rules nested under a draft cycle.
 */
class EntitlementRuleController extends Controller
{
    public function __construct(private readonly EntitlementPlanner $planner) {}

    public function store(EntitlementRuleRequest $request, AssistanceCycle $cycle): RedirectResponse
    {
        $data = $this->normalise($request->validated());
        $this->planner->assertNoOverlap($cycle, $data);

        $cycle->rules()->create($data);

        return redirect()->route('cycles.show', $cycle)->with('success', 'Entitlement rule added.');
    }

    public function update(EntitlementRuleRequest $request, AssistanceCycle $cycle, EntitlementRule $rule): RedirectResponse
    {
        abort_unless($rule->assistance_cycle_id === $cycle->id, 404);

        $data = $this->normalise($request->validated());
        $this->planner->assertNoOverlap($cycle, $data, $rule);

        $rule->update($data);

        return redirect()->route('cycles.show', $cycle)->with('success', 'Entitlement rule updated.');
    }

    public function destroy(AssistanceCycle $cycle, EntitlementRule $rule): RedirectResponse
    {
        $this->authorize('manageRules', $cycle);
        abort_unless($rule->assistance_cycle_id === $cycle->id, 404);

        $rule->delete();

        return redirect()->route('cycles.show', $cycle)->with('success', 'Entitlement rule removed.');
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function normalise(array $data): array
    {
        $data['max_household_size'] = ($data['max_household_size'] ?? '') === '' ? null : (int) $data['max_household_size'];
        $data['max_quantity'] = ($data['max_quantity'] ?? '') === '' ? null : (float) $data['max_quantity'];

        return $data;
    }
}
