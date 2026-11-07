<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Feature 12 – append-only audit trail (timeline) of a repair request.
 */
class ShelterRepairEvent extends Model
{
    protected $fillable = ['shelter_repair_request_id', 'user_id', 'action', 'from_status', 'to_status', 'note'];

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(ShelterRepairRequest::class, 'shelter_repair_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
