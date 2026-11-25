<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/*
 * The event page (M09): one shareable URL per event, `/events/{id}-{slug}`,
 * with the event's details and counts as public data only. Time is frozen at
 * Wed 25 Nov 2026 12:00 UTC. Who may open pending and hidden events is
 * EventVisibilityTest (Slice 2).
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-11-25 12:00', 'UTC'));
});

function winterCup(array $attributes = []): Event
{
    $shop = Shop::factory()->create([
        'name' => 'Carta Alta',
        'address' => 'Rua das Flores 12',
        'city' => 'Lisboa',
        'district' => 'Lisboa',
        'latitude' => 38.7578,
        'longitude' => -9.25036,
    ]);

    return Event::factory()->for($shop)->create([
        'type' => EventType::Cup,
        'title' => 'Winter Cup',
        'display_id' => '26-11-001729',
        'official_url' => 'https://example.test/tournaments/26-11-001729/',
        'starts_at' => Carbon::parse('2026-11-29 10:00', 'UTC'),
        ...$attributes,
    ]);
}

it('shows the event\'s details and nothing else', function () {
    $event = winterCup();

    $this->get("/events/{$event->id}-winter-cup")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('events/show')
            // No etc(): a field the test does not name fails it, so nothing extra reaches the page.
            ->has('event', fn (AssertableInertia $data) => $data
                ->where('id', $event->id)
                ->where('url', url("/events/{$event->id}-winter-cup"))
                ->where('displayId', '26-11-001729')
                ->where('source', 'pokedata')
                ->where('type', 'cup')
                ->where('title', 'Winter Cup')
                ->where('shopName', 'Carta Alta')
                ->where('address', 'Rua das Flores 12')
                ->where('district', 'Lisboa')
                ->where('city', 'Lisboa')
                ->where('latitude', 38.7578)
                ->where('longitude', -9.25036)
                ->where('startsAt', '2026-11-29T10:00:00Z')
                ->where('endsAt', '2026-11-29T16:00:00Z') // a Cup lasts 6 hours
                ->where('officialUrl', 'https://example.test/tournaments/26-11-001729/')
                ->where('goingCount', 0)
                ->where('confirmedCount', 0)
                ->where('isOngoing', false)
                ->where('hasEnded', false)
                ->where('isCancelled', false)
                ->where('possiblyCancelled', false)
                ->where('canAnswer', true)));
});

it('counts 12 going and 8 confirmed, only for this event', function () {
    $event = winterCup();
    Attendance::factory()->count(8)->for($event)->create(['confirmed_at' => now()]);
    Attendance::factory()->count(4)->for($event)->create();
    Attendance::factory()->count(3)->for(Event::factory())->create(); // another event's answers

    $this->get("/events/{$event->id}-winter-cup")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('event.goingCount', 12)
            ->where('event.confirmedCount', 8));
});

it('redirects to the canonical URL and keeps the query string', function (string $slugPart) {
    $event = winterCup();

    $this->get("/events/{$event->id}{$slugPart}?going")
        ->assertStatus(301)
        ->assertRedirect(url("/events/{$event->id}-winter-cup?going"));
})->with([
    'no slug' => '',
    'a wrong slug' => '-old-name',
]);

it('says whether the event has started, ended or been cancelled, and if answers are open', function (array $attributes, array $expected) {
    $event = winterCup($attributes);

    $this->get("/events/{$event->id}-winter-cup")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('event.isOngoing', $expected[0])
            ->where('event.hasEnded', $expected[1])
            ->where('event.isCancelled', $expected[2])
            ->where('event.possiblyCancelled', $expected[3])
            ->where('event.canAnswer', $expected[4]));
})->with([
    // [isOngoing, hasEnded, isCancelled, possiblyCancelled, canAnswer]
    'upcoming' => [[], [false, false, false, false, true]],
    'ongoing' => [['starts_at' => Carbon::parse('2026-11-25 09:00', 'UTC')], [true, false, false, false, true]],
    'ended' => [['starts_at' => Carbon::parse('2026-11-24 10:00', 'UTC')], [false, true, false, false, false]],
    'cancelled' => [['status' => EventStatus::Cancelled], [false, false, true, false, false]],
    'possibly cancelled' => [['possibly_cancelled_at' => Carbon::parse('2026-11-25 03:00', 'UTC')], [false, false, false, true, true]],
]);

it('falls back to the type and shop for an event without a title', function () {
    $event = winterCup(['title' => null]);

    $this->get("/events/{$event->id}-league-cup-at-carta-alta")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('event.title', 'League Cup at Carta Alta')
            ->where('event.url', url("/events/{$event->id}-league-cup-at-carta-alta")));
});

it('sends no coordinates for a shop without them', function () {
    $event = winterCup();
    $event->shop->update(['latitude' => null, 'longitude' => null]);

    $this->get("/events/{$event->id}-winter-cup")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('event.latitude', null)
            ->where('event.longitude', null));
});

it('returns 404 for an unknown event', function () {
    $this->get('/events/999999-winter-cup')->assertNotFound();
});
