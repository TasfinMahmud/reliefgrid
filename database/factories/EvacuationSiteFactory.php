<?php

namespace Database\Factories;

use App\Models\Block;
use App\Models\EvacuationSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EvacuationSite> */
class EvacuationSiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'EV-'.fake()->unique()->numerify('##'),
            'name' => fake()->randomElement(['Community centre', 'Primary school', 'Mosque hall', 'Health post']),
            'block_id' => Block::factory(),
            'capacity' => fake()->numberBetween(100, 800),
            'current_occupancy' => 0,
            'is_open' => true,
        ];
    }
}
