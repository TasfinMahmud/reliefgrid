<?php

namespace Database\Factories;

use App\Enums\HouseholdStatus;
use App\Models\Block;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Household> */
class HouseholdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fn () => Household::nextCode(),
            'block_id' => Block::factory(),
            'head_name' => fake()->name(),
            'head_sex' => fake()->randomElement(['female', 'male']),
            'head_age' => fake()->numberBetween(18, 80),
            'phone' => fake()->optional()->numerify('01#########'),
            'household_size' => fake()->numberBetween(1, 9),
            'shelter_number' => fake()->bothify('S-###'),
            'status' => HouseholdStatus::Active,
            'consent_given' => true,
            'consent_given_at' => now(),
            'consent_version' => Household::CONSENT_VERSION,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => HouseholdStatus::Inactive,
            'deactivated_at' => now(),
            'deactivation_reason' => 'Relocated',
        ]);
    }
}
