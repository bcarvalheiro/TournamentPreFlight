<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use Illuminate\Database\UniqueConstraintViolationException;

/*
 * A row means "this profile is going to this event" (CLAUDE.md rule 5:
 * one answer per profile per event; not going means no row).
 */
it('refuses a second answer from the same profile for the same event', function () {
    $event = Event::factory()->create();
    $profile = PlayerProfile::factory()->create();
    Attendance::factory()->for($event)->for($profile)->create();

    expect(fn () => Attendance::factory()->for($event)->for($profile)->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows several anonymised answers for one event', function () {
    $event = Event::factory()->create();

    Attendance::factory()->count(2)->for($event)->create([
        'player_profile_id' => null,
        'anonymised_at' => now(),
    ]);

    expect($event->attendances()->count())->toBe(2);
});

it('deletes a profile\'s answers when the profile is deleted', function () {
    $event = Event::factory()->create();
    $leaving = PlayerProfile::factory()->create();
    $staying = PlayerProfile::factory()->create();
    Attendance::factory()->for($event)->for($leaving)->create();
    Attendance::factory()->for($event)->for($staying)->create();

    $leaving->delete();

    expect($event->attendances()->pluck('player_profile_id')->all())->toBe([$staying->id]);
});

it('counts the going players of an event', function () {
    [$busy, $quiet] = Event::factory()->count(2)->create();
    Attendance::factory()->count(3)->for($busy)->create();
    Attendance::factory()->for($quiet)->create();

    $counts = Event::query()->withCount('attendances')->pluck('attendances_count', 'id');

    expect($counts[$busy->id])->toBe(3)
        ->and($counts[$quiet->id])->toBe(1);
});
