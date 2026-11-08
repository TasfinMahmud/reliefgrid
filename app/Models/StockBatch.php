<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Feature 5 – A batch (lot) of one item in one warehouse, with an expiry date.
 *
 * The batch never stores a running balance: the balance is always derived
 * from the append-only movement ledger (see StockService::batchBalance()).
 */
class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id', 'item_id', 'batch_number', 'expiry_date', 'supplier',
        'received_quantity', 'received_at', 'received_by', 'origin_batch_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'received_at' => 'date',
            'received_quantity' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function originBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'origin_batch_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderBy('created_at')->orderBy('id');
    }

    /** Current balance derived from the ledger (sum of signed quantities). */
    public function balance(): float
    {
        return (float) $this->movements()->sum('quantity');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function expiresWithin(int $days): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->lte(Carbon::today()->addDays($days));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')->whereDate('expiry_date', '<', Carbon::today());
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '>=', Carbon::today())
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays($days));
    }

    /** Batches with positive balance, FEFO order (first-expired, first-out). */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expiry_date')
            ->orderBy('received_at');
    }
}
