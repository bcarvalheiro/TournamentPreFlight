<?php

use App\Enums\EventStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;

/*
 * M08 AC1, AC3, AC4: a player answers "going" or "not going" for their own
 * trainers, one or several in one request. A row means going, so answering
 * twice never makes a second row. Someone else's trainer is refused (403) and
 * so is an event players cannot answer for (422: not listed, or ended); a
 * refused request saves nothing at all.
 */

/** Rows for this trainer and event: 0 or 1, never more. */
function answers(PlayerProfile $profile, Event $event): int
{
    return Attendance::query()
        ->where('player_profile_id', $profile->id)
        ->where('event_id', $event->id)
        ->count();
}

it('leaves 1, 1, then 0 rows for going, going again, then not going', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();
    $this->actingAs($profile->user);

    $answer = fn (bool $going) => $this->put(route('events.attendance.update', $event), [
        'profiles' => [$profile->id => $going],
    ])->assertRedirect();

    $answer(true);
    expect(answers($profile, $event))->toBe(1);

    $answer(true);
    expect(answers($profile, $event))->toBe(1);

    $answer(false);
    expect(answers($profile, $event))->toBe(0);
});

it('saves different choices for several trainers in one request', function () {
    $parent = User::factory()->create();
    $adult = PlayerProfile::factory()->for($parent)->create();
    $child = PlayerProfile::factory()->child()->for($parent)->create();
    $event = Event::factory()->create();
    Attendance::factory()->for($adult)->for($event)->create();

    $this->actingAs($parent)
        ->put(route('events.attendance.update', $event), [
            'profiles' => [$adult->id => false, $child->id => true],
        ])
        ->assertRedirect();

    expect(answers($adult, $event))->toBe(0)
        ->and(answers($child, $event))->toBe(1);
});

it('refuses another account\'s trainer and saves nothing', function () {
    $own = PlayerProfile::factory()->create();
    $someoneElses = PlayerProfile::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($own->user)
        ->putJson(route('events.attendance.update', $event), [
            'profiles' => [$own->id => true, $someoneElses->id => true],
        ])
        ->assertForbidden();

    // The account's own trainer, sent in the same request, is rolled back too.
    expect(Attendance::query()->count())->toBe(0);
});

it('refuses a trainer id that does not exist, like someone else\'s', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();

    // An unknown id gets the same 403 as another account's trainer (decisions.md, 24 Nov),
    // so nobody can tell which ids exist.
    $this->actingAs($profile->user)
        ->putJson(route('events.attendance.update', $event), [
            'profiles' => [$profile->id => true, 999999 => true],
        ])
        ->assertForbidden();

    expect(Attendance::query()->count())->toBe(0);
});

it('refuses a malformed answer', function (array $body, string $field) {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();

    // The shape is checked before anything else (AttendanceRequest), so the ids need not exist.
    $this->actingAs($profile->user)
        ->putJson(route('events.attendance.update', $event), $body)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(Attendance::query()->count())->toBe(0);
})->with([
    'no answers' => [[], 'profiles'],
    'an empty map' => [['profiles' => []], 'profiles'],
    'not a yes or no' => [['profiles' => [1 => 'maybe']], 'profiles.1'],
    // "01" or "5abc" would be read as trainer 1 or 5 by the database (review fix).
    'a key that is not a whole number' => [['profiles' => ['01' => true]], 'profiles'],
]);

it('accepts an event that has started but not ended', function () {
    $this->freezeTime();
    $profile = PlayerProfile::factory()->create();
    // A casual event lasts 2 hours: started an hour ago, it is still on (product.md, Events).
    $event = Event::factory()->create(['starts_at' => now()->subHour()]);

    $this->actingAs($profile->user)
        ->put(route('events.attendance.update', $event), ['profiles' => [$profile->id => true]])
        ->assertRedirect();

    expect(answers($profile, $event))->toBe(1);
});

it('refuses an event players cannot answer for', function (array $attributes) {
    $this->freezeTime();
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create($attributes);

    $this->actingAs($profile->user)
        ->putJson(route('events.attendance.update', $event), [
            'profiles' => [$profile->id => true],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['event' => 'Answers are closed for this event.']);

    expect(Attendance::query()->count())->toBe(0);
})->with([
    'pending' => [['status' => EventStatus::Pending]],
    'hidden' => [['status' => EventStatus::Hidden]],
    'cancelled' => [['status' => EventStatus::Cancelled]],
    // A casual event lasts 2 hours, so one that started 3 hours ago has ended.
    'ended' => fn () => ['starts_at' => now()->subHours(3)],
]);

it('sends a guest to sign in', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();

    $this->put(route('events.attendance.update', $event), [
        'profiles' => [$profile->id => true],
    ])->assertRedirect(route('login'));

    expect(Attendance::query()->count())->toBe(0);
});
