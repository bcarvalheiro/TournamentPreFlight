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
 * Time is frozen so the tests never depend on the real date; a test may
 * travel elsewhere before seeding.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-20 12:00', 'UTC'));
});

it('seeds the demo data with the expected count per table', function () {
    $this->seed();

    expect([
        'users' => User::query()->count(),
        'player_profiles' => PlayerProfile::query()->count(),
        'shops' => Shop::query()->count(),
        'events' => Event::query()->count(),
        'attendances' => Attendance::query()->count(),
    ])->toBe([
        'users' => 63,
        'player_profiles' => 64,
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

it('keeps the demo children children in any year', function () {
    $this->travelTo(Carbon::parse('2031-03-01 12:00', 'UTC'));

    $this->seed();

    $family = User::query()->where('email', 'family@preflight.test')->firstOrFail();

    expect($family->playerProfiles()->where('is_child', true)->count())->toBe(2);
});

it('never sends a crowd player to two events on the same day', function () {
    $this->seed();

    $sameDayAnswers = Attendance::query()
        ->join('events', 'events.id', '=', 'attendances.event_id')
        ->selectRaw('player_profile_id, DATE(starts_at) AS day, COUNT(*) AS answers')
        ->groupBy('player_profile_id', 'day')
        ->having('answers', '>', 1)
        ->count();

    expect($sameDayAnswers)->toBe(0);
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

/*
 * The demo world includes an admin with a known password, so it must never
 * reach a real server, even if someone runs db:seed there.
 */
it('seeds nothing outside local and testing', function () {
    app()->detectEnvironment(fn (): string => 'production');

    // --force skips production's "Are you sure?", as a deploy script would.
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(User::query()->count())->toBe(0)
        ->and(Shop::query()->count())->toBe(0)
        ->and(Event::query()->count())->toBe(0);
});
