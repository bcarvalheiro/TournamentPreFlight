<?php

namespace Database\Factories;

use App\Models\ImportRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRun>
 */
class ImportRunFactory extends Factory
{
    /**
     * Define the model's default state: a successful nightly run.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now('Europe/Lisbon')->subDay()->setTime(3, 30)->utc();

        return [
            'source' => 'pokedata',
            'started_at' => $startedAt,
            'finished_at' => $startedAt->copy()->addSeconds(12),
            'status' => 'ok',
            'events_created' => fake()->numberBetween(0, 10),
            'events_updated' => fake()->numberBetween(0, 10),
            'events_flagged' => 0,
            'events_unchanged' => fake()->numberBetween(700, 850),
        ];
    }
}
