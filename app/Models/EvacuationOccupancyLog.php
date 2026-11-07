<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature 14 – History of occupancy readings for an evacuation site.
 */
class EvacuationOccupancyLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['evacuation_site_id', 'previous_occupancy', 'occupancy', 'recorded_by', 'note'];

    protected function casts(): array
    {
        return [
            'previous_occupancy' => 'integer',
            'occupancy' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(EvacuationSite::class, 'evacuation_site_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
