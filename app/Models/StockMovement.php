<?php

namespace App\Models;

use App\Enums\MovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

/**
 * Feature 5 – One entry in the append-only stock movement ledger.
 *
 * Inbound entries (receipt, transfer_in) have a positive quantity; outbound
 * entries (issue, transfer_out) a negative one. Rows can never be updated or
 * deleted: a mistake is corrected by writing a compensating entry.
 */
class StockMovement extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'warehouse_id', 'item_id', 'stock_batch_id', 'type', 'quantity',
        'reference_type', 'reference_id', 'note', 'created_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Guard the ledger: it is append-only.
        static::updating(function () {
            throw new LogicException('Stock movements are append-only and cannot be modified.');
        });
        static::deleting(function () {
            throw new LogicException('Stock movements are append-only and cannot be deleted.');
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The business document that caused this movement (transfer, repair request...). */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function referenceLabel(): ?string
    {
        if (! $this->reference_type) {
            return null;
        }
        $short = class_basename($this->reference_type);

        return match ($short) {
            'StockTransfer' => 'Transfer #'.$this->reference_id,
            'ShelterRepairRequest' => 'Repair request #'.$this->reference_id,
            'AssistanceCycle' => 'Cycle #'.$this->reference_id,
            default => $short.' #'.$this->reference_id,
        };
    }
}
