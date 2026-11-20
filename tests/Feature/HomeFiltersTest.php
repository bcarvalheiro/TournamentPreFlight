<?php

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/*
 * Type and shop filters from the query string (M05 AC3, with AC2 and AC4):
 * values within a group are OR'd, the groups are AND'd, bad values are dropped.
 * Time is frozen at Sat 5 Dec 2026 12:00 UTC.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    $this->shopA = Shop::factory()->create();
    $this->shopB = Shop::factory()->create();
    $this->shopC = Shop::factory()->create();
});

/** A future event; each call starts one day later, so the order is the call order. */
function filterableEvent(EventType $type, Shop $shop): Event
{
    static $day = 0;
    $day = $day % 20 + 1;

    return Event::factory()->create([
        'type' => $type,
        'shop_id' => $shop->id,
        'starts_at' => Carbon::parse('2026-12-05 10:00', 'UTC')->addDays($day),
    ]);
}

/**
 * @return list<int>
 */
function listedIds(AssertableInertia $page): array
{
    return collect($page->toArray()['props']['events'])->pluck('id')->sort()->values()->all();
}

it('filters by type alone', function () {
    $cup = filterableEvent(EventType::Cup, $this->shopA);
    filterableEvent(EventType::Casual, $this->shopA);
    filterableEvent(EventType::Challenge, $this->shopB);

    $this->get(route('home', ['type' => ['cup']]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('events.0.id', $cup->id)
            ->has('events', 1)
            ->where('totals', ['matched' => 1, 'all' => 3])
        );
});

it('shows events of any chosen type', function () {
    $cup = filterableEvent(EventType::Cup, $this->shopA);
    $challenge = filterableEvent(EventType::Challenge, $this->shopB);
    filterableEvent(EventType::Casual, $this->shopA);

    $this->get(route('home', ['type' => ['cup', 'challenge']]))
        ->assertInertia(fn (AssertableInertia $page) => expect(listedIds($page))
            ->toBe(collect([$cup->id, $challenge->id])->sort()->values()->all())
        );
});

it('shows events at any chosen shop', function () {
    $atA = filterableEvent(EventType::Casual, $this->shopA);
    $atB = filterableEvent(EventType::Casual, $this->shopB);
    filterableEvent(EventType::Casual, $this->shopC);

    $this->get(route('home', ['shop' => [$this->shopA->id, $this->shopB->id]]))
        ->assertInertia(fn (AssertableInertia $page) => expect(listedIds($page))
            ->toBe(collect([$atA->id, $atB->id])->sort()->values()->all())
        );
});

it('combines type and shop, and echoes the filters', function () {
    $cupAtA = filterableEvent(EventType::Cup, $this->shopA);
    filterableEvent(EventType::Cup, $this->shopB);
    filterableEvent(EventType::Casual, $this->shopA);

    $this->get(route('home', ['type' => ['cup'], 'shop' => [$this->shopA->id]]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 1)
            ->where('events.0.id', $cupAtA->id)
            ->where('filters', ['type' => ['cup'], 'shop' => [$this->shopA->id], 'when' => 'any'])
        );
});

it('keeps up next the same whatever the filters', function () {
    $casual = filterableEvent(EventType::Casual, $this->shopA);
    $cup = filterableEvent(EventType::Cup, $this->shopB);

    $this->get(route('home', ['type' => ['cup']]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 1)
            ->has('upNext', 2)
            ->where('upNext.0.id', $casual->id)
            ->where('upNext.1.id', $cup->id)
        );
});

it('drops filter values it does not know', function () {
    filterableEvent(EventType::Cup, $this->shopA);
    filterableEvent(EventType::Casual, $this->shopB);

    $this->get(route('home', ['type' => ['friendly'], 'shop' => ['abc']]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 2)
            ->where('filters', ['type' => [], 'shop' => [], 'when' => 'any'])
        );
});
