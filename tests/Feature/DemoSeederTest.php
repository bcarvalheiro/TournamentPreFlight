<?php

use App\Enums\EventType;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;

/*
 * AC4: `php artisan migrate:fresh --seed` produces the demo world the UI is
 * built on. $this->seed() runs DatabaseSeeder, the same thing --seed runs.
 */
it('seeds the demo data with the expected count per table', function () {
    $this->seed();

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
    $this->seed();

    $upcoming = Event::upcoming()->get();

    expect($upcoming)->toHaveCount(29)
        ->and($upcoming->first()?->starts_at->isPast())->toBeTrue();
});

it('seeds the family account with two consented children and an admin', function () {
    $this->seed();

    $family = User::query()->where('email', 'family@preflight.test')->firstOrFail();
    $children = $family->playerProfiles()->where('is_child', true)->get();

    expect($family->playerProfiles)->toHaveCount(3)
        ->and($children)->toHaveCount(2)
        ->and($children->every(fn (PlayerProfile $child) => $child->consent_by_user_id === $family->id))->toBeTrue()
        ->and(User::query()->where('email', 'admin@preflight.test')->value('is_admin'))->toBeTruthy();
});

/*
 * CLAUDE.md rule 4: the seeder's times are Lisbon wall-clock times, stored in
 * UTC. In July Lisbon is UTC+1, so 10:00 in the shop is 09:00 UTC (in winter
 * the two match, which would hide a missing conversion).
 */
it('stores seeded Lisbon times in UTC', function () {
    $this->travelTo(Carbon::parse('2027-07-01 08:00', 'UTC'));

    $this->seed();

    $cup = Event::query()
        ->whereRelation('shop', 'name', 'Toca do Tabuleiro')
        ->where('type', EventType::Cup)
        ->orderBy('starts_at')
        ->firstOrFail();

    expect($cup->starts_at->toDateTimeString())->toBe('2027-07-04 09:00:00');
});
