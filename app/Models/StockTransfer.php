<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Feature 5 – A request to move stock between two hubs. Requires coordinator
 * approval; approval writes the matching ledger entries at both ends.
 */
class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_warehouse_id', 'to_warehouse_id', 'item_id', 'stock_batch_id', 'quantity',
        'status', 'reason', 'requested_by', 'requested_at', 'reviewed_by', 'reviewed_at',
        'review_note', 'destination_batch_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'quantity' => 'decimal:2',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function destinationBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'destination_batch_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function isPending(): bool
    {
        return $this->status === TransferStatus::Pending;
    }
}
