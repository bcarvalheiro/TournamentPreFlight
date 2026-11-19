<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventReport>
 */
class EventReportFactory extends Factory
{
    /**
     * Define the model's default state: a reported change.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'type' => 'changed',
            'message' => fake()->sentence(),
        ];
    }
}
