<?php

namespace Database\Factories;

use App\Models\MagicLoginToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MagicLoginToken>
 */
class MagicLoginTokenFactory extends Factory
{
    /**
     * Define the model's default state: an unused link, valid for 15 minutes.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'token_hash' => hash('sha256', Str::random(40)),
            'expires_at' => now()->addMinutes(15),
            'used_at' => null,
            'ip' => fake()->ipv4(),
        ];
    }
}
