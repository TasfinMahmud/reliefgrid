<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Item> */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???-###')),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'category' => fake()->randomElement(['food', 'nfi', 'shelter', 'wash']),
            'unit' => fake()->randomElement(['kg', 'pcs', 'litre']),
            'is_active' => true,
        ];
    }
}
