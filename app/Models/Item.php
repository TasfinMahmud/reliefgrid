<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalogue item: anything that can be stocked, entitled or issued
 * (food, non-food items, shelter materials, fuel...).
 */
class Item extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'food' => 'Food',
        'nfi' => 'Non-food item',
        'shelter' => 'Shelter material',
        'wash' => 'WASH / hygiene',
        'fuel' => 'Fuel',
        'other' => 'Other',
    ];

    protected $fillable = ['code', 'name', 'category', 'unit', 'is_active', 'description'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function entitlementRules(): HasMany
    {
        return $this->hasMany(EntitlementRule::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->unit})";
    }
}
