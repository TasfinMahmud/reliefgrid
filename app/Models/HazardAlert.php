<?php

namespace App\Models;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\HazardType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Feature 14 – A block-level hazard alert issued by a coordinator.
 */
class HazardAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'hazard_type', 'severity', 'title', 'message', 'status', 'issued_by', 'issued_at',
        'expires_at', 'cancelled_by', 'cancelled_at', 'cancel_reason', 'instructions',
    ];

    protected function casts(): array
    {
        return [
            'hazard_type' => HazardType::class,
            'severity' => AlertSeverity::class,
            'status' => AlertStatus::class,
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class)->orderBy('code');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /** Active = status active and not past its expiry time. */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->where('status', AlertStatus::Active->value)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            });
    }

    public function isCurrentlyActive(): bool
    {
        return $this->status === AlertStatus::Active
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /** Effective status, taking expiry into account even if the row was not updated. */
    public function effectiveStatus(): AlertStatus
    {
        if ($this->status === AlertStatus::Active && $this->expires_at !== null && $this->expires_at->isPast()) {
            return AlertStatus::Expired;
        }

        return $this->status;
    }
}
