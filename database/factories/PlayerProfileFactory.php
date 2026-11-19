<?php

namespace Database\Factories;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerProfile>
 */
class PlayerProfileFactory extends Factory
{
    /**
     * Define the model's default state: an adult player.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'pop_id' => fake()->unique()->numerify('#######'),
            'birth_year' => fake()->numberBetween(1975, 2005),
        ];
    }

    /**
     * A child profile (10 this year) with the owner's recorded consent.
     */
    public function child(): static
    {
        return $this->state(fn (array $attributes) => [
            'birth_year' => now('Europe/Lisbon')->year - 10,
            'consent_given_at' => now(),
            'consent_by_user_id' => fn (array $attributes) => $attributes['user_id'],
        ]);
    }
}
