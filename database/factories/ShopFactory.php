<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();
        $city = fake()->city();

        return [
            'name' => $name,
            'address' => fake()->streetAddress(),
            'city' => $city,
            'district' => null,
            'latitude' => null,
            'longitude' => null,
            'official_name_key' => mb_strtolower("{$name}|{$city}"),
        ];
    }
}
