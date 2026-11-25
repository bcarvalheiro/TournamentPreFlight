<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\PlayerProfile;
use Illuminate\Support\Carbon;

/*
 * M09 AC6: a shared `/events/{id}-{slug}?going` link asks "Going to …?" on an
 * event people can still answer, and opening it writes nothing: chat apps
 * open links to build previews and browsers prefetch them, so only a tap may
 * mark going (decisions.md, 23 Nov).
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-25 12:00', 'UTC'));
    $this->event = Event::factory()->create([
        'title' => 'Winter Cup',
        'starts_at' => Carbon::parse('2026-11-29 10:00', 'UTC'),
    ]);
    $this->url = "/events/{$this->event->id}-winter-cup";
});

it('asks "Going to …?" only when the link says ?going', function (string $query, bool $asks) {
    expect($this->get($this->url.$query)->assertOk()->inertiaProps('askGoing'))->toBe($asks);
})->with([
    'with ?going' => ['?going', true],
    'without it' => ['', false],
]);

it('does not ask once answers are closed', function (array $attributes) {
    $this->event->update($attributes);

    expect($this->get($this->url.'?going')->assertOk()->inertiaProps('askGoing'))->toBeFalse();
})->with([
    'ended' => [['starts_at' => Carbon::parse('2026-11-24 10:00', 'UTC')]],
    'cancelled' => [['status' => EventStatus::Cancelled]],
]);

it('writes nothing when the link is opened', function (bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(PlayerProfile::factory()->create()->user);
    }

    $this->get($this->url.'?going')
        ->assertOk()
        ->assertSessionMissing('pending_intent');

    expect($this->event->attendances()->count())->toBe(0);
})->with([
    'as a guest' => false,
    'as a player' => true,
]);
