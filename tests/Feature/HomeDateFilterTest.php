<?php

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

/*
 * Date presets on the home page (M05 AC3, AC4), counted in Lisbon calendar
 * days (architecture.md, "Time"). Each test freezes time, then checks events
 * just inside and just outside the edge of the range. In winter Lisbon is on
 * UTC, in summer on UTC+1; the July test shows the week ends at Lisbon midnight.
 */

/** A casual event (2 hours) starting at the given UTC time. */
function datedEvent(string $startsAtUtc): Event
{
    return Event::factory()->create([
        'type' => EventType::Casual,
        'starts_at' => Carbon::parse($startsAtUtc, 'UTC'),
    ]);
}

/**
 * @param  array<string, mixed>  $query
 * @return list<int>
 */
function idsListedWith(array $query): array
{
    return collect(test()->get(route('home', $query))->inertiaProps('events'))->pluck('id')->all();
}

it('ends "this week" on Sunday, seen from a Wednesday', function () {
    $this->travelTo(Carbon::parse('2026-12-02 12:00', 'UTC')); // Wednesday
    $sunday = datedEvent('2026-12-06 20:00');
    datedEvent('2026-12-07 10:00');

    expect(idsListedWith(['when' => 'week']))->toBe([$sunday->id]);
});

it('keeps only that day as "this week" on a Sunday', function () {
    $this->travelTo(Carbon::parse('2026-12-06 09:00', 'UTC')); // Sunday
    $tonight = datedEvent('2026-12-06 18:00');
    datedEvent('2026-12-07 10:00');

    expect(idsListedWith(['when' => 'week']))->toBe([$tonight->id]);
});

it('means the coming Saturday and Sunday as "this weekend" on a weekday', function () {
    $this->travelTo(Carbon::parse('2026-12-02 12:00', 'UTC')); // Wednesday
    datedEvent('2026-12-04 20:00');
    $saturday = datedEvent('2026-12-05 10:00');
    $sunday = datedEvent('2026-12-06 20:00');
    datedEvent('2026-12-07 10:00');

    expect(idsListedWith(['when' => 'weekend']))->toBe([$saturday->id, $sunday->id]);
});

it('means the rest of the current weekend as "this weekend" on a Sunday', function () {
    $this->travelTo(Carbon::parse('2026-12-06 09:00', 'UTC')); // Sunday
    $tonight = datedEvent('2026-12-06 18:00');
    datedEvent('2026-12-12 10:00');

    expect(idsListedWith(['when' => 'weekend']))->toBe([$tonight->id]);
});

it('counts "next 7 days" as today plus six, across a year end', function () {
    $this->travelTo(Carbon::parse('2026-12-28 12:00', 'UTC')); // Monday
    $lastDay = datedEvent('2027-01-03 20:00');
    datedEvent('2027-01-04 10:00');

    expect(idsListedWith(['when' => '7']))->toBe([$lastDay->id]);
});

it('counts "next 30 days" as today plus 29, across a month end', function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    $lastDay = datedEvent('2027-01-03 20:00');
    datedEvent('2027-01-04 10:00');

    expect(idsListedWith(['when' => '30']))->toBe([$lastDay->id]);
});

it('ends the week at Lisbon midnight, not UTC midnight, in summer', function () {
    $this->travelTo(Carbon::parse('2027-07-07 12:00', 'UTC')); // Wednesday
    $sundayLisbon = datedEvent('2027-07-11 22:30'); // 23:30 Sunday in Lisbon
    datedEvent('2027-07-11 23:30'); // 00:30 Monday in Lisbon

    expect(idsListedWith(['when' => 'week']))->toBe([$sundayLisbon->id]);
});

it('applies the date filter before the cap of 25', function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    foreach (range(0, 39) as $hours) {
        datedEvent(Carbon::parse('2026-12-06 00:00', 'UTC')->addHours($hours)->toDateTimeString());
    }
    foreach (range(20, 29) as $day) {
        datedEvent("2026-12-{$day} 10:00");
    }

    $this->get(route('home', ['when' => '7']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 25)
            ->where('totals', ['matched' => 40, 'all' => 50])
            ->where('filters.when', '7')
        );
});

it('shows every event for a "when" it does not know', function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    datedEvent('2026-12-06 10:00');
    datedEvent('2027-02-06 10:00');

    $this->get(route('home', ['when' => 'fortnight']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 2)
            ->where('filters.when', 'any')
        );
});

it('keeps events from the start of From to the end of To', function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    datedEvent('2026-12-09 20:00');
    $firstDay = datedEvent('2026-12-10 00:30');
    $lastDay = datedEvent('2026-12-12 22:00');
    datedEvent('2026-12-13 10:00');

    $this->get(route('home', ['when' => 'custom', 'from' => '2026-12-10', 'to' => '2026-12-12']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 2)
            ->where('events.0.id', $firstDay->id)
            ->where('events.1.id', $lastDay->id)
            ->where('filters.when', 'custom')
            ->where('filters.from', '2026-12-10')
            ->where('filters.to', '2026-12-12')
        );
});

it('leaves the end open when only From is picked', function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    datedEvent('2026-12-11 10:00');
    $fromDay = datedEvent('2026-12-12 10:00');
    $farAway = datedEvent('2027-03-01 10:00');

    expect(idsListedWith(['when' => 'custom', 'from' => '2026-12-12']))->toBe([$fromDay->id, $farAway->id]);
});

it('starts today when only To is picked', function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    $tomorrow = datedEvent('2026-12-06 10:00');
    datedEvent('2026-12-08 10:00');

    expect(idsListedWith(['when' => 'custom', 'to' => '2026-12-07']))->toBe([$tomorrow->id]);
});

it('drops picked dates it cannot use', function (array $query) {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    datedEvent('2026-12-06 10:00');
    datedEvent('2026-12-20 10:00');

    $this->get(route('home', ['when' => 'custom', ...$query]))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 2)
            ->where('filters.when', 'any')
            ->where('filters.from', null)
            ->where('filters.to', null)
        );
})->with([
    'not a date' => [['from' => 'soon']],
    'a day that does not exist' => [['to' => '2027-02-31']],
    'From in the past' => [['from' => '2026-12-01']],
    'To before From' => [['from' => '2026-12-12', 'to' => '2026-12-10']],
    'no dates at all' => [[]],
]);

it('ignores picked dates unless "Pick dates" is chosen', function () {
    $this->travelTo(Carbon::parse('2026-12-05 12:00', 'UTC'));
    datedEvent('2026-12-06 10:00');
    datedEvent('2026-12-30 10:00');

    $this->get(route('home', ['from' => '2026-12-24']))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('events', 2)
            ->where('filters.when', 'any')
            ->where('filters.from', null)
        );
});
