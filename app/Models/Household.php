<?php

namespace App\Models;

use App\Enums\HouseholdStatus;
use App\Enums\TokenStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Feature 1 – Household registered for assistance.
 * Holds the minimal data needed to plan and verify entitlements.
 */
class Household extends Model
{
    use HasFactory;

    public const CONSENT_VERSION = 'v1.0-2026';

    protected $fillable = [
        'code', 'block_id', 'head_name', 'head_sex', 'head_age', 'phone',
        'household_size', 'shelter_number', 'vulnerability_notes', 'status',
        'consent_given', 'consent_given_at', 'consent_version',
        'registered_by', 'deactivated_at', 'deactivated_by', 'deactivation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => HouseholdStatus::class,
            'consent_given' => 'boolean',
            'consent_given_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'household_size' => 'integer',
            'head_age' => 'integer',
        ];
    }

    // --- Relationships -------------------------------------------------

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class)->orderByDesc('issued_at');
    }

    public function activeToken(): HasOne
    {
        return $this->hasOne(Token::class)->where('status', TokenStatus::Active->value);
    }

    public function events(): HasMany
    {
        return $this->hasMany(HouseholdEvent::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    // --- Scopes --------------------------------------------------------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', HouseholdStatus::Active->value);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }
        $like = '%'.strtolower($term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('LOWER(code) LIKE ?', [$like])
                ->orWhereRaw('LOWER(head_name) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', [$like])
                ->orWhereRaw('LOWER(COALESCE(shelter_number, \'\')) LIKE ?', [$like]);
        });
    }

    // --- Helpers -------------------------------------------------------

    public function isActive(): bool
    {
        return $this->status === HouseholdStatus::Active;
    }

    /** Generate the next sequential household code, e.g. HH-000123. */
    public static function nextCode(): string
    {
        $maxId = (int) static::max('id');

        return sprintf('HH-%06d', $maxId + 1);
    }
}
