<?php

namespace Database\Factories;

use App\Enums\EventSourceKey;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shop_id' => Shop::factory(),
            'source' => EventSourceKey::Pokedata,
            'official_guid' => fake()->unique()->uuid(),
            'official_url' => null,
            'display_id' => null,
            'type' => EventType::Casual,
            'title' => null,
            'starts_at' => fake()->dateTimeBetween('+1 day', '+6 weeks'),
            'status' => EventStatus::Listed,
        ];
    }
}
