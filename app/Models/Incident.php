<?php

namespace App\Models;

use App\Enums\HazardType;
use App\Enums\IncidentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Feature 14 – An incident logged in a block and followed through
 * investigation and response to a verified closure.
 */
class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'block_id', 'hazard_type', 'title', 'description', 'occurred_at', 'status',
        'reported_by', 'hazard_alert_id', 'evacuation_site_id',
        'households_affected', 'people_affected', 'injuries', 'deaths',
        'investigated_by', 'investigation_started_at', 'investigation_notes',
        'response_lead_id', 'response_started_at', 'response_notes',
        'verified_by', 'closed_at', 'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'hazard_type' => HazardType::class,
            'status' => IncidentStatus::class,
            'occurred_at' => 'datetime',
            'investigation_started_at' => 'datetime',
            'response_started_at' => 'datetime',
            'closed_at' => 'datetime',
            'households_affected' => 'integer',
            'people_affected' => 'integer',
            'injuries' => 'integer',
            'deaths' => 'integer',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function investigatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigated_by');
    }

    public function responseLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'response_lead_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function hazardAlert(): BelongsTo
    {
        return $this->belongsTo(HazardAlert::class);
    }

    public function evacuationSite(): BelongsTo
    {
        return $this->belongsTo(EvacuationSite::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', IncidentStatus::Closed->value);
    }

    public function isClosed(): bool
    {
        return $this->status === IncidentStatus::Closed;
    }

    public static function nextCode(): string
    {
        return sprintf('INC-%05d', ((int) static::max('id')) + 1);
    }
}
