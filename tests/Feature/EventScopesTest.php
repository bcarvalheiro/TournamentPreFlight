<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Carbon;

/*
 * The query pieces the home page (M05) builds on. Time is frozen at
 * Sat 5 Dec 2026 12:00 UTC; casual events last 2 hours.
 */
beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
});

function casualAt(string $startsAt, array $attributes = []): Event
{
    return Event::factory()->create([
        'type' => EventType::Casual,
        'starts_at' => Carbon::parse($startsAt, 'UTC'),
        ...$attributes,
    ]);
}

it('includes an ongoing event in upcoming', function () {
    $ongoing = casualAt('2026-12-05 11:00');

    expect(Event::upcoming()->pluck('id')->all())->toBe([$ongoing->id]);
});

it('excludes an ended event from upcoming', function () {
    casualAt('2026-12-05 09:00');

    expect(Event::upcoming()->count())->toBe(0);
});

it('excludes an event ending exactly now from upcoming', function () {
    casualAt('2026-12-05 10:00');

    expect(Event::upcoming()->count())->toBe(0);
});

it('excludes events that are not listed from upcoming', function (EventStatus $status) {
    casualAt('2026-12-05 18:00', ['status' => $status]);

    expect(Event::upcoming()->count())->toBe(0);
})->with([EventStatus::Pending, EventStatus::Hidden, EventStatus::Cancelled]);

it('orders upcoming by start', function () {
    $evening = casualAt('2026-12-05 18:00');
    $tomorrow = casualAt('2026-12-06 10:00');
    $afternoon = casualAt('2026-12-05 14:00');

    expect(Event::upcoming()->pluck('id')->all())
        ->toBe([$afternoon->id, $evening->id, $tomorrow->id]);
});

it('keeps only the given types', function () {
    Event::factory()->create(['type' => EventType::Casual]);
    $cup = Event::factory()->create(['type' => EventType::Cup]);
    $challenge = Event::factory()->create(['type' => EventType::Challenge]);

    expect(Event::ofTypes([EventType::Cup, EventType::Challenge])->pluck('id')->all())
        ->toEqualCanonicalizing([$cup->id, $challenge->id]);
});

it('keeps only the given shops', function () {
    [$shopA, $shopB, $shopC] = Shop::factory()->count(3)->create();
    $atA = Event::factory()->for($shopA)->create();
    Event::factory()->for($shopB)->create();
    $atC = Event::factory()->for($shopC)->create();

    expect(Event::atShops([$shopA->id, $shopC->id])->pluck('id')->all())
        ->toEqualCanonicalizing([$atA->id, $atC->id]);
});

it('keeps events starting in the range, both ends included', function () {
    $atFrom = casualAt('2026-12-07 00:00');
    $inside = casualAt('2026-12-08 15:00');
    $atTo = casualAt('2026-12-09 23:59');
    casualAt('2026-12-10 00:00');

    $found = Event::between(Carbon::parse('2026-12-07 00:00', 'UTC'), Carbon::parse('2026-12-09 23:59', 'UTC'));

    expect($found->pluck('id')->all())->toEqualCanonicalizing([$atFrom->id, $inside->id, $atTo->id]);
});

it('leaves the range open when no end is given', function () {
    $nextWeek = casualAt('2026-12-12 10:00');
    $nextYear = casualAt('2027-12-12 10:00');

    $found = Event::between(Carbon::parse('2026-12-07 00:00', 'UTC'), null);

    expect($found->pluck('id')->all())->toEqualCanonicalizing([$nextWeek->id, $nextYear->id]);
});

/*
 * CLAUDE.md rule 4: M05 computes date ranges in Lisbon time. In July Lisbon is
 * UTC+1, so Lisbon's 10 July runs from 23:00 UTC on the 9th to 22:59:59 UTC on
 * the 10th.
 */
it('keeps events by moment whatever time zone the range is given in', function () {
    $justAfterLisbonMidnight = casualAt('2027-07-09 23:30');
    casualAt('2027-07-10 23:30');

    $found = Event::between(
        Carbon::parse('2027-07-10 00:00:00', 'Europe/Lisbon'),
        Carbon::parse('2027-07-10 23:59:59', 'Europe/Lisbon'),
    );

    expect($found->pluck('id')->all())->toBe([$justAfterLisbonMidnight->id]);
});
