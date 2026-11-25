<?php

use App\Enums\EventStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Carbon;

/*
 * M09 AC2 and Slice 5: the event page's `own` prop gives a player their own
 * trainers and who of them is going, so the page can show toggle rows. Nobody
 * else gets it (their "I'm going" uses the pending intent), and nobody gets it
 * once answers are closed: no controls on an ended or cancelled event.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-25 12:00', 'UTC'));
    $this->event = Event::factory()->create([
        'title' => 'Winter Cup',
        'starts_at' => Carbon::parse('2026-11-29 10:00', 'UTC'),
    ]);
});

function eventPageOwn(Event $event): mixed
{
    return test()->get("/events/{$event->id}-winter-cup")->assertOk()->inertiaProps('own');
}

it('gives a guest no own data', function () {
    expect(eventPageOwn($this->event))->toBeNull();
});

it('gives an account that is not a player yet no own data', function (User $user) {
    $this->actingAs($user);

    expect(eventPageOwn($this->event))->toBeNull();
})->with([
    'no trainer' => fn () => User::factory()->create(),
    'unverified email' => fn () => PlayerProfile::factory()->for(User::factory()->unverified())->create()->user,
]);

it('gives a player their trainers and who of them is going', function () {
    $parent = User::factory()->create();
    $ana = PlayerProfile::factory()->for($parent)->create(['name' => 'Ana']);
    $tiago = PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Tiago']);
    Attendance::factory()->for($ana)->for($this->event)->create();
    Attendance::factory()->count(2)->for($this->event)->create(); // other players

    $this->actingAs($parent);

    expect(eventPageOwn($this->event))->toEqual([
        'trainers' => [['id' => $ana->id, 'name' => 'Ana'], ['id' => $tiago->id, 'name' => 'Tiago']],
        'going' => [$this->event->id => [$ana->id]],
    ]);
});

it('gives no own data once answers are closed', function (array $attributes) {
    $this->event->update($attributes);
    $this->actingAs(PlayerProfile::factory()->create()->user);

    expect(eventPageOwn($this->event))->toBeNull();
})->with([
    'ended' => [['starts_at' => Carbon::parse('2026-11-24 10:00', 'UTC')]],
    'cancelled' => [['status' => EventStatus::Cancelled]],
]);
