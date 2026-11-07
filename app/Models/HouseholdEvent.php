<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature 1 – Append-only history of everything that happened to a household
 * (registration, updates, consent, token issue/reissue, deactivation...).
 */
class HouseholdEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['household_id', 'user_id', 'event_type', 'description', 'changes'];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeClass(): string
    {
        return match ($this->event_type) {
            'registered' => 'bg-success',
            'updated' => 'bg-info text-dark',
            'deactivated' => 'bg-danger',
            'reactivated' => 'bg-success',
            'token_issued' => 'bg-primary',
            'token_reissued' => 'bg-warning text-dark',
            'token_revoked' => 'bg-danger',
            'consent_recorded' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}
