<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;

/*
 * "Add to calendar" (M09 Slice 10): `/events/{id}/calendar.ics` is an
 * iCalendar file (RFC 5545) that Apple Calendar and Outlook open. Times in
 * UTC (rule 4), the type's estimated end, the shop's address and a link back
 * to the page; no player data (checked in NoPersonalDataLeaksTest).
 */

/** The file's lines, split on the CRLF the standard requires. */
function calendarLines(TestResponse $response): array
{
    return explode("\r\n", rtrim((string) $response->getContent(), "\r\n"));
}

it('downloads the event as a calendar file', function () {
    $shop = Shop::factory()->create(['name' => 'Carta Alta', 'address' => 'Rua das Flores 12', 'city' => 'Lisboa']);
    // A winter Sunday: Lisbon is on UTC, 10:00 there is 10:00 UTC; a League Cup lasts about 6 hours.
    $event = Event::factory()->for($shop)->create([
        'type' => EventType::Cup,
        'title' => 'Winter Cup',
        'starts_at' => Carbon::parse('2026-11-29 10:00', 'UTC'),
    ]);

    $response = $this->get(route('events.calendar', $event))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('text/calendar; charset=utf-8');
    expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename="winter-cup.ics"');

    $lines = calendarLines($response);
    expect($lines[0])->toBe('BEGIN:VCALENDAR');
    expect(end($lines))->toBe('END:VCALENDAR');
    expect($lines)->toContain(
        'BEGIN:VEVENT',
        "UID:event-{$event->id}@".parse_url((string) config('app.url'), PHP_URL_HOST),
        'DTSTART:20261129T100000Z',
        'DTEND:20261129T160000Z',
        'SUMMARY:Winter Cup',
        'LOCATION:Carta Alta\, Rua das Flores 12\, Lisboa',
        'URL:'.route('events.show', ['event' => $event, 'slug' => 'winter-cup']),
        'END:VEVENT',
    );
});

it('keeps a summer start in UTC, not in Lisbon time', function () {
    // 09:00 UTC is 10:00 in Lisbon in July; the file says 09:00Z and the calendar app
    // shows it in the viewer's zone. Stored as UTC (rule 4), as Eloquent saves the wall time.
    $event = Event::factory()->create(['starts_at' => Carbon::parse('2026-07-12 09:00', 'UTC')]);

    expect(calendarLines($this->get(route('events.calendar', $event))))
        ->toContain('DTSTART:20260712T090000Z');
});

it('names an untitled event after its type and shop', function () {
    $event = Event::factory()->for(Shop::factory()->create(['name' => 'Carta Alta']))
        ->create(['type' => EventType::Cup, 'title' => null]);

    expect(calendarLines($this->get(route('events.calendar', $event))))
        ->toContain('SUMMARY:League Cup at Carta Alta');
});

it('escapes commas, semicolons and backslashes in text', function () {
    $event = Event::factory()->for(Shop::factory()->create([
        'name' => 'Cards, Coffee; Co',
        'address' => 'Rua A\\B',
        'city' => 'Porto',
    ]))->create();

    expect(calendarLines($this->get(route('events.calendar', $event))))
        ->toContain('LOCATION:Cards\, Coffee\; Co\, Rua A\\\\B\, Porto');
});

it('turns carriage returns into escaped new lines', function () {
    // Review (M09): a bare \r (an import or, from M11, a submission) would end the line early.
    $event = Event::factory()->create(['title' => "Winter\r\nCup\rFinal"]);

    $response = $this->get(route('events.calendar', $event));

    expect(calendarLines($response))->toContain('SUMMARY:Winter\\nCup\\nFinal');
    expect(preg_match_all("/\r(?!\n)/", (string) $response->getContent()))->toBe(0);
});

it('marks a cancelled event as cancelled', function () {
    // Review (M09): the page stays public with a note; a file added before (or re-imported)
    // must not look like a normal event. A listed one says nothing about its status.
    $cancelled = Event::factory()->create(['status' => EventStatus::Cancelled]);
    $listed = Event::factory()->create();

    expect(calendarLines($this->get(route('events.calendar', $cancelled))))->toContain('STATUS:CANCELLED');
    expect(calendarLines($this->get(route('events.calendar', $listed))))->not->toContain('STATUS:CANCELLED');
});

it('folds long lines at 75 bytes, as the standard asks', function () {
    $title = trim(str_repeat('Grand Winter Championship Weekend ', 4));
    $event = Event::factory()->create(['title' => $title]);

    $lines = calendarLines($this->get(route('events.calendar', $event)));

    expect(collect($lines)->map(strlen(...))->max())->toBeLessThanOrEqual(75);
    // A folded line goes on with one space; unfolded, the title is whole again.
    expect(str_replace("\r\n ", '', implode("\r\n", $lines)))->toContain("SUMMARY:{$title}");
});

it('hides pending and hidden events from guests, like the page', function (EventStatus $status) {
    $event = Event::factory()->create(['status' => $status]);

    $this->get(route('events.calendar', $event))->assertNotFound();
})->with([EventStatus::Pending, EventStatus::Hidden]);
