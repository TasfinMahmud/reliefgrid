<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature 2 – A rule describing how much of an item a household receives,
 * based on its size.
 *
 * quantity = base_quantity + per_member_quantity * household_size
 * (capped at max_quantity when set), for households whose size is within
 * [min_household_size, max_household_size].
 */
class EntitlementRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'assistance_cycle_id', 'item_id', 'min_household_size', 'max_household_size',
        'base_quantity', 'per_member_quantity', 'max_quantity', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'min_household_size' => 'integer',
            'max_household_size' => 'integer',
            'base_quantity' => 'decimal:2',
            'per_member_quantity' => 'decimal:2',
            'max_quantity' => 'decimal:2',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AssistanceCycle::class, 'assistance_cycle_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function appliesTo(int $householdSize): bool
    {
        if ($householdSize < $this->min_household_size) {
            return false;
        }
        if ($this->max_household_size !== null && $householdSize > $this->max_household_size) {
            return false;
        }

        return true;
    }

    /** Compute the entitled quantity for a household of the given size. */
    public function quantityFor(int $householdSize): float
    {
        $qty = (float) $this->base_quantity + (float) $this->per_member_quantity * $householdSize;
        if ($this->max_quantity !== null) {
            $qty = min($qty, (float) $this->max_quantity);
        }

        return round(max($qty, 0), 2);
    }

    public function sizeBandLabel(): string
    {
        return $this->max_household_size === null
            ? "{$this->min_household_size}+ members"
            : "{$this->min_household_size}–{$this->max_household_size} members";
    }

    public function formulaLabel(): string
    {
        $parts = [];
        if ((float) $this->base_quantity > 0) {
            $parts[] = number_format((float) $this->base_quantity, 2);
        }
        if ((float) $this->per_member_quantity > 0) {
            $parts[] = number_format((float) $this->per_member_quantity, 2).' × members';
        }
        $label = $parts ? implode(' + ', $parts) : '0';
        if ($this->max_quantity !== null) {
            $label .= ' (max '.number_format((float) $this->max_quantity, 2).')';
        }

        return $label;
    }
}
