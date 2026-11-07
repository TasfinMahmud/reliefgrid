<?php

namespace App\Models;

use App\Enums\RepairStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature 12 – a shelter repair request raised for one household.
 */
class ShelterRepairRequest extends Model
{
    public const DAMAGE_TYPES = [
        'roof' => 'Roof / covering',
        'walls' => 'Walls / frame',
        'floor' => 'Floor / foundation',
        'door_window' => 'Door / window',
        'drainage' => 'Drainage / waterproofing',
        'other' => 'Other',
    ];

    public const SEVERITIES = [
        'minor' => 'Minor',
        'moderate' => 'Moderate',
        'major' => 'Major',
        'critical' => 'Critical',
    ];

    protected $fillable = [
        'code', 'household_id', 'block_id', 'damage_type', 'severity', 'description', 'status',
        'reported_by', 'assessed_by', 'approved_by', 'rejected_by', 'completed_by',
        'assessed_at', 'approved_at', 'rejected_at', 'completed_at',
        'assessment_notes', 'estimated_days', 'rejection_reason', 'completion_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => RepairStatus::class,
            'estimated_days' => 'integer',
            'assessed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // --- Relations -----------------------------------------------------

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ShelterRepairMaterial::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ShelterVerificationPhoto::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShelterRepairEvent::class);
    }

    // --- Scopes --------------------------------------------------------

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (RepairStatus $s) => $s->value, RepairStatus::open()));
    }

    // --- Helpers -------------------------------------------------------

    public function damageTypeLabel(): string
    {
        return self::DAMAGE_TYPES[$this->damage_type] ?? ucfirst((string) $this->damage_type);
    }

    public function severityLabel(): string
    {
        return $this->severity ? (self::SEVERITIES[$this->severity] ?? ucfirst($this->severity)) : '—';
    }

    public function hasGeoTaggedPhoto(): bool
    {
        return $this->photos()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->exists();
    }

    public function hasMaterials(): bool
    {
        return $this->materials()->exists();
    }

    public function allMaterialsIssued(): bool
    {
        $materials = $this->materials()->get();

        return $materials->isNotEmpty()
            && $materials->every(fn (ShelterRepairMaterial $m) => $m->isFullyIssued());
    }
}
