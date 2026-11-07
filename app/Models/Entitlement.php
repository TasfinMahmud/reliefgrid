<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature 2 – A materialised entitlement: what one household is due for one
 * item in one activated assistance cycle.
 */
class Entitlement extends Model
{
    use HasFactory;

    protected $fillable = ['assistance_cycle_id', 'household_id', 'item_id', 'quantity', 'household_size_at_activation'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'household_size_at_activation' => 'integer',
        ];
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AssistanceCycle::class, 'assistance_cycle_id');
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
