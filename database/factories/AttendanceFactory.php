<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state: going, not confirmed.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'player_profile_id' => PlayerProfile::factory(),
        ];
    }
}
