<?php

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/*
 * The shop list of the filter sheet (M05 AC6): only shops with upcoming
 * events, by name, each counting its upcoming events under the type and
 * date filters (never the shop filter); shops at 0 stay listed.
 * Time is frozen at Sat 5 Dec 2026 12:00 UTC.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    $this->alfa = Shop::factory()->create(['name' => 'Alfa', 'city' => 'Lisboa']);
    $this->zeta = Shop::factory()->create(['name' => 'Zeta', 'city' => 'Porto']);
});

function shopEvent(Shop $shop, string $startsAtUtc, EventType $type = EventType::Casual): Event
{
    return Event::factory()->create([
        'shop_id' => $shop->id,
        'type' => $type,
        'starts_at' => Carbon::parse($startsAtUtc, 'UTC'),
    ]);
}

it('lists only shops with upcoming events, by name, with their counts', function () {
    shopEvent($this->zeta, '2026-12-06 10:00');
    shopEvent($this->zeta, '2026-12-07 10:00');
    shopEvent($this->alfa, '2026-12-06 10:00');
    shopEvent($this->alfa, '2026-12-05 08:00'); // ended at 10:00
    shopEvent(Shop::factory()->create(['name' => 'Ghost']), '2026-12-05 08:00');
    Shop::factory()->create(['name' => 'Empty']);

    $this->get(route('home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('shops', [
                ['id' => $this->alfa->id, 'name' => 'Alfa', 'city' => 'Lisboa', 'eventCount' => 1],
                ['id' => $this->zeta->id, 'name' => 'Zeta', 'city' => 'Porto', 'eventCount' => 2],
            ])
        );
});

it('counts under the type and date filters, keeping shops at zero', function () {
    shopEvent($this->alfa, '2026-12-06 10:00', EventType::Cup);
    shopEvent($this->alfa, '2026-12-25 10:00', EventType::Cup);
    shopEvent($this->zeta, '2026-12-06 10:00');

    $this->get(route('home', ['type' => ['cup'], 'when' => '7']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('shops.0.name', 'Alfa')
            ->where('shops.0.eventCount', 1)
            ->where('shops.1.name', 'Zeta')
            ->where('shops.1.eventCount', 0)
        );
});

it('does not let the shop filter change the counts', function () {
    shopEvent($this->alfa, '2026-12-06 10:00');
    shopEvent($this->zeta, '2026-12-06 10:00');

    $this->get(route('home', ['shop' => [$this->alfa->id]]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 1)
            ->where('shops.1.name', 'Zeta')
            ->where('shops.1.eventCount', 1)
        );
});
