<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;
use App\Models\User;

/*
 * AC4: `php artisan migrate:fresh --seed` produces the demo world the UI is
 * built on. $this->seed() runs DatabaseSeeder, the same thing --seed runs.
 */
beforeEach(function () {
    $this->seed();
});

it('seeds the demo data with the expected count per table', function () {
    expect([
        'users' => User::query()->count(),
        'player_profiles' => PlayerProfile::query()->count(),
        'shops' => Shop::query()->count(),
        'events' => Event::query()->count(),
        'attendances' => Attendance::query()->count(),
    ])->toBe([
        'users' => 43,
        'player_profiles' => 44,
        'shops' => 9,
        'events' => 30,
        'attendances' => 315,
    ]);
});

it('seeds one ongoing and one ended event', function () {
    $upcoming = Event::upcoming()->get();

    expect($upcoming)->toHaveCount(29)
        ->and($upcoming->first()?->starts_at->isPast())->toBeTrue();
});

it('seeds the family account with two consented children and an admin', function () {
    $family = User::query()->where('email', 'family@preflight.test')->firstOrFail();
    $children = $family->playerProfiles()->where('is_child', true)->get();

    expect($family->playerProfiles)->toHaveCount(3)
        ->and($children)->toHaveCount(2)
        ->and($children->every(fn (PlayerProfile $child) => $child->consent_by_user_id === $family->id))->toBeTrue()
        ->and(User::query()->where('email', 'admin@preflight.test')->value('is_admin'))->toBeTruthy();
});
