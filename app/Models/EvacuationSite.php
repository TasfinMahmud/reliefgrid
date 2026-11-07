<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature 14 – An evacuation site with a capacity and tracked occupancy.
 */
class EvacuationSite extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'block_id', 'capacity', 'current_occupancy', 'is_open',
        'facilities', 'contact_person', 'contact_phone', 'latitude', 'longitude',
    ];

    protected $attributes = [
        'current_occupancy' => 0,
        'is_open' => true,
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'current_occupancy' => 'integer',
            'is_open' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function occupancyLogs(): HasMany
    {
        return $this->hasMany(EvacuationOccupancyLog::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function availableCapacity(): int
    {
        return max($this->capacity - $this->current_occupancy, 0);
    }

    public function occupancyRate(): float
    {
        return $this->capacity > 0 ? round($this->current_occupancy / $this->capacity * 100, 1) : 0.0;
    }

    public function isFull(): bool
    {
        return $this->current_occupancy >= $this->capacity;
    }

    public function statusLabel(): string
    {
        if (! $this->is_open) {
            return 'Closed';
        }

        return $this->isFull() ? 'Full' : 'Open';
    }

    public function statusBadgeClass(): string
    {
        if (! $this->is_open) {
            return 'bg-secondary';
        }
        if ($this->isFull()) {
            return 'bg-danger';
        }

        return $this->occupancyRate() >= 80 ? 'bg-warning text-dark' : 'bg-success';
    }
}
