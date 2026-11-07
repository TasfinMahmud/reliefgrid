<?php

namespace Database\Factories;

use App\Models\Block;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Block> */
class BlockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'B-'.fake()->unique()->numerify('##'),
            'name' => 'Block '.fake()->unique()->lexify('??'),
            'camp' => 'Camp 4',
            'sector' => fake()->randomElement(['North', 'South', 'East', 'West']),
            'latitude' => fake()->latitude(21.1, 21.3),
            'longitude' => fake()->longitude(92.1, 92.2),
            'estimated_population' => fake()->numberBetween(500, 5000),
        ];
    }
}
