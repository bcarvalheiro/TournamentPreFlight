<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventFollow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventFollow>
 */
class EventFollowFactory extends Factory
{
    /**
     * Define the model's default state: notify once at 16 going.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'notify_at_count' => 16,
            'notify_on_drop' => false,
        ];
    }
}
