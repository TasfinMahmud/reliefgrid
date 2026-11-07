<?php

namespace App\Models;

use App\Enums\CycleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature 2 – An assistance cycle (e.g. "November 2026 food distribution").
 *
 * While in `draft` the entitlement rules can be edited and previewed.
 * Activation (coordinator approval) locks the rules and materialises the
 * entitlements for every eligible household.
 */
class AssistanceCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'starts_on', 'ends_on', 'status',
        'created_by', 'activated_by', 'activated_at', 'closed_by', 'closed_at',
        'eligible_household_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => CycleStatus::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'activated_at' => 'datetime',
            'closed_at' => 'datetime',
            'eligible_household_count' => 'integer',
        ];
    }

    public function rules(): HasMany
    {
        return $this->hasMany(EntitlementRule::class)->orderBy('item_id')->orderBy('min_household_size');
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(Entitlement::class);
    }

    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(Block::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CycleStatus::Active->value);
    }

    public function isDraft(): bool
    {
        return $this->status === CycleStatus::Draft;
    }

    public function isActive(): bool
    {
        return $this->status === CycleStatus::Active;
    }

    public function isClosed(): bool
    {
        return $this->status === CycleStatus::Closed;
    }

    /** Rules are locked as soon as the cycle leaves the draft state. */
    public function rulesLocked(): bool
    {
        return ! $this->isDraft();
    }

    /** Households in scope: active households in the selected blocks (or all blocks). */
    public function eligibleHouseholdsQuery(): Builder
    {
        $query = Household::query()->active()->with('block');
        $blockIds = $this->blocks()->pluck('blocks.id');
        if ($blockIds->isNotEmpty()) {
            $query->whereIn('block_id', $blockIds);
        }

        return $query->orderBy('code');
    }
}
