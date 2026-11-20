<?php

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Support\Carbon;

/*
 * ends_at is always the type's estimate (decisions.md, M03 review and M04):
 * set on create, recomputed whenever the start or the type changes.
 */
it('sets ends_at from the type\'s estimate', function (EventType $type, string $expectedEnd) {
    $event = Event::factory()->create([
        'type' => $type,
        'starts_at' => Carbon::parse('2026-12-05 10:00', 'UTC'),
    ]);

    expect($event->ends_at->toDateTimeString())->toBe($expectedEnd);
})->with([
    'casual, 2 hours' => [EventType::Casual, '2026-12-05 12:00:00'],
    'prerelease, 3 hours' => [EventType::Prerelease, '2026-12-05 13:00:00'],
    'challenge, 4 hours' => [EventType::Challenge, '2026-12-05 14:00:00'],
    'cup, 6 hours' => [EventType::Cup, '2026-12-05 16:00:00'],
]);

it('recomputes ends_at when the start moves', function () {
    $event = Event::factory()->create([
        'type' => EventType::Cup,
        'starts_at' => Carbon::parse('2026-12-05 10:00', 'UTC'),
    ]);

    $event->update(['starts_at' => Carbon::parse('2026-12-05 14:00', 'UTC')]);

    expect($event->fresh()?->ends_at->toDateTimeString())->toBe('2026-12-05 20:00:00');
});

it('recomputes ends_at when the type changes', function () {
    $event = Event::factory()->create([
        'type' => EventType::Casual,
        'starts_at' => Carbon::parse('2026-12-05 10:00', 'UTC'),
    ]);

    $event->update(['type' => EventType::Cup]);

    expect($event->fresh()?->ends_at->toDateTimeString())->toBe('2026-12-05 16:00:00');
});
