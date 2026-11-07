<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Feature 12 – a geo-tagged verification photograph proving the repair on site.
 * Coordinates come from the JPEG EXIF GPS block or from the capturing device (browser geolocation).
 */
class ShelterVerificationPhoto extends Model
{
    public const SOURCE_EXIF = 'exif';
    public const SOURCE_DEVICE = 'device';

    protected $fillable = [
        'shelter_repair_request_id', 'path', 'original_name', 'caption',
        'latitude', 'longitude', 'accuracy_m', 'geo_source', 'captured_at', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy_m' => 'float',
            'captured_at' => 'datetime',
        ];
    }

    public function repairRequest(): BelongsTo
    {
        return $this->belongsTo(ShelterRepairRequest::class, 'shelter_repair_request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function mapUrl(): string
    {
        return sprintf('https://www.openstreetmap.org/?mlat=%1$.6f&mlon=%2$.6f#map=18/%1$.6f/%2$.6f', $this->latitude, $this->longitude);
    }
}
