<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;

/*
 * M08 AC2: a player with one trainer taps "I'm going" on a slip and the page
 * answers in place: a toast, the count and the label change without a full
 * reload; a second tap takes it back. The click, the partial reload and the
 * toast need a real browser.
 */
it('lets a solo player tap to go, then tap again to stop', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->for(Shop::factory()->create(['name' => 'Carta Alta']))->create();
    $this->actingAs($profile->user);

    $page = visit(route('home'));

    // The event is on the pairings sheet and in the agenda; use the agenda's
    // slip (a Card, data-slot="card"), whose only button is the quick button.
    $slip = '[data-slot="card"]';

    // The count with its screen-reader words, so a start time like 10:00 cannot match.
    $page->assertSeeIn($slip, '0 trainers going')
        ->click("{$slip} button")
        ->assertSee("You're going to Carta Alta")
        ->assertSeeIn($slip, 'Going ✓')
        ->assertSeeIn($slip, '1 trainers going');

    expect(Attendance::query()->where('event_id', $event->id)->count())->toBe(1);

    $page->click("{$slip} button")
        ->assertSee("You're no longer going to Carta Alta")
        ->assertSeeIn($slip, "I'm going")
        ->assertSeeIn($slip, '0 trainers going');

    expect(Attendance::query()->where('event_id', $event->id)->count())->toBe(0);
});
