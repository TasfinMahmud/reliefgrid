<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A settlement block: the geographic unit used by households, alerts,
 * evacuation sites, incidents and warehouses.
 */
class Block extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'camp', 'sector', 'latitude', 'longitude',
        'estimated_population', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'estimated_population' => 'integer',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} – {$this->name}";
    }
}
