<?php

use App\Jobs\CheckThresholds;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

/*
 * M08 scope: every change to an event's answers queues `CheckThresholds`
 * (architecture.md, Notifications), which emails followers in M12. Only a real
 * change counts: a tap that changes nothing queues nothing, and a refused
 * request, rolled back, queues nothing either.
 */

beforeEach(function () {
    Queue::fake();
});

it('queues the job once, for that event, when a trainer starts going', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($profile->user)
        ->put(route('events.attendance.update', $event), ['profiles' => [$profile->id => true]]);

    Queue::assertPushedOnce(CheckThresholds::class, fn (CheckThresholds $job) => $job->event->is($event));
});

it('queues the job once when a family saves two trainers', function () {
    $parent = User::factory()->create();
    $adult = PlayerProfile::factory()->for($parent)->create();
    $child = PlayerProfile::factory()->child()->for($parent)->create();
    $event = Event::factory()->create();

    $this->actingAs($parent)
        ->put(route('events.attendance.update', $event), ['profiles' => [$adult->id => true, $child->id => true]]);

    Queue::assertPushedTimes(CheckThresholds::class, 1);
});

it('queues the job when a trainer stops going', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();
    Attendance::factory()->for($profile)->for($event)->create();

    $this->actingAs($profile->user)
        ->put(route('events.attendance.update', $event), ['profiles' => [$profile->id => false]]);

    Queue::assertPushedOnce(CheckThresholds::class);
});

it('queues nothing when the answer does not change', function () {
    $going = PlayerProfile::factory()->create();
    $notGoing = PlayerProfile::factory()->create();
    $event = Event::factory()->create();
    Attendance::factory()->for($going)->for($event)->create();

    $this->actingAs($going->user)
        ->put(route('events.attendance.update', $event), ['profiles' => [$going->id => true]]);
    $this->actingAs($notGoing->user)
        ->put(route('events.attendance.update', $event), ['profiles' => [$notGoing->id => false]]);

    Queue::assertNothingPushed();
});

it('queues nothing when the request is refused', function () {
    $own = PlayerProfile::factory()->create();
    $someoneElses = PlayerProfile::factory()->create();
    $event = Event::factory()->create();

    // The own trainer comes first (lower id), so its answer is saved, then rolled back.
    $this->actingAs($own->user)
        ->put(route('events.attendance.update', $event), [
            'profiles' => [$own->id => true, $someoneElses->id => true],
        ])
        ->assertForbidden();

    Queue::assertNothingPushed();
});
