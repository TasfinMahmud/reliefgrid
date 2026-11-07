<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature 12 – a material line needed for a repair (planned at assessment, issued by the warehouse).
 */
class ShelterRepairMaterial extends Model
{
    protected $fillable = [
        'shelter_repair_request_id', 'item_name', 'unit', 'quantity_required', 'quantity_issued', 'stock_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:2',
            'quantity_issued' => 'decimal:2',
        ];
    }

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(ShelterRepairRequest::class, 'shelter_repair_request_id');
    }

    public function remaining(): float
    {
        return max(0.0, round((float) $this->quantity_required - (float) $this->quantity_issued, 2));
    }

    public function isFullyIssued(): bool
    {
        return $this->remaining() <= 0.0;
    }
}
