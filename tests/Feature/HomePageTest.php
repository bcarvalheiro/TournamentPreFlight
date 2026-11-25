<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/*
 * What the home page lists (M05 AC1, AC5). Time is frozen at
 * Sat 5 Dec 2026 12:00 UTC; casual events last 2 hours. Which events count
 * as upcoming is tested in EventScopesTest; here we check the page uses it.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
});

function eventAt(string $startsAt, array $attributes = []): Event
{
    return Event::factory()->create([
        'type' => EventType::Casual,
        'starts_at' => Carbon::parse($startsAt, 'UTC'),
        ...$attributes,
    ]);
}

it('lists upcoming and ongoing events by start, never ended or unlisted ones', function () {
    $later = eventAt('2026-12-12 10:00');
    eventAt('2026-12-05 09:00'); // ended at 11:00
    $ongoing = eventAt('2026-12-05 11:00');
    eventAt('2026-12-06 10:00', ['status' => EventStatus::Hidden]);
    $tomorrow = eventAt('2026-12-06 15:00');

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('home')
            ->has('events', 3)
            ->where('events.0.id', $ongoing->id)
            ->where('events.0.isOngoing', true)
            ->where('events.1.id', $tomorrow->id)
            ->where('events.1.isOngoing', false)
            ->where('events.2.id', $later->id)
        );
});

it('marks an event ongoing from its start, not a second before', function () {
    $startsNow = eventAt('2026-12-05 12:00:00');
    $startsInASecond = eventAt('2026-12-05 12:00:01');

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('events.0.id', $startsNow->id)
            ->where('events.0.isOngoing', true)
            ->where('events.1.id', $startsInASecond->id)
            ->where('events.1.isOngoing', false)
        );
});

it('shows at most 25 events', function () {
    foreach (range(1, 26) as $day) {
        eventAt(Carbon::parse('2026-12-06 10:00', 'UTC')->addDays($day - 1)->toDateTimeString());
    }

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 25)
            ->where('events.24.startsAt', '2026-12-30T10:00:00Z')
        );
});

it('shows the first 5 upcoming events as up next', function () {
    eventAt('2026-12-05 09:00'); // ended at 11:00
    $ongoing = eventAt('2026-12-05 11:00');
    $next = collect(range(6, 11))->map(fn (int $day) => eventAt("2026-12-{$day} 10:00"));

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('upNext', 5)
            ->where('upNext.0.id', $ongoing->id)
            ->where('upNext.4.id', $next[3]->id)
        );
});

it('counts every matching event, not only the 25 shown', function () {
    foreach (range(0, 39) as $hours) {
        eventAt(Carbon::parse('2026-12-06 10:00', 'UTC')->addHours($hours)->toDateTimeString());
    }

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 25)
            ->where('totals', ['matched' => 40, 'all' => 40])
        );
});

it('counts the going answers of each event', function () {
    $popular = eventAt('2026-12-06 10:00');
    eventAt('2026-12-07 10:00');
    Attendance::factory()->count(3)->create(['event_id' => $popular->id]);

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('events.0.goingCount', 3)
            ->where('events.1.goingCount', 0)
        );
});

it('sends the card fields, with type and shop as the title when the event has none', function () {
    $shop = Shop::factory()->create(['name' => 'Carta Alta', 'city' => 'Porto']);
    $cup = eventAt('2026-12-06 10:00', [
        'shop_id' => $shop->id,
        'type' => EventType::Cup,
        'display_id' => '26-12-000123',
        'possibly_cancelled_at' => now(),
    ]);
    eventAt('2026-12-07 10:00', ['title' => 'Winter Cup']);

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events.0', fn (AssertableInertia $card) => $card
                ->where('url', url("/events/{$cup->id}-league-cup-at-carta-alta"))
                ->where('displayId', '26-12-000123')
                ->where('type', 'cup')
                ->where('title', 'League Cup at Carta Alta')
                ->where('shopName', 'Carta Alta')
                ->where('city', 'Porto')
                ->where('startsAt', '2026-12-06T10:00:00Z')
                ->where('endsAt', '2026-12-06T16:00:00Z')
                ->where('possiblyCancelled', true)
                ->etc()
            )
            ->where('events.1.title', 'Winter Cup')
            ->where('events.1.possiblyCancelled', false)
        );
});
