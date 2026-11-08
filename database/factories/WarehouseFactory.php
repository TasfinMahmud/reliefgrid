<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Warehouse> */
class WarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'WH-'.strtoupper(fake()->unique()->lexify('???')),
            'name' => fake()->city().' hub',
            'is_active' => true,
        ];
    }
}
