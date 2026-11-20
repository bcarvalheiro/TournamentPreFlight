<?php

use App\Models\Event;
use App\Models\EventFollow;
use App\Models\EventReport;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/*
 * Follows ("tell me when 16 are going") and reports ("Report a change").
 * One follow per user per event (AC2), so nobody gets the same email twice.
 */
it('refuses a second follow by the same user on the same event', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();
    EventFollow::factory()->for($event)->for($user)->create(['notify_at_count' => 16]);

    expect(fn () => EventFollow::factory()->for($event)->for($user)->create(['notify_at_count' => 8]))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('lets different users follow the same event', function () {
    $event = Event::factory()->create();

    EventFollow::factory()->count(2)->for($event)->create();

    expect(EventFollow::query()->where('event_id', $event->id)->count())->toBe(2);
});

it('deletes a user\'s follows and reports when the account is deleted', function () {
    $user = User::factory()->create();
    EventFollow::factory()->for($user)->create();
    EventReport::factory()->for($user)->create();

    $user->delete();

    expect(EventFollow::query()->count())->toBe(0)
        ->and(EventReport::query()->count())->toBe(0);
});
