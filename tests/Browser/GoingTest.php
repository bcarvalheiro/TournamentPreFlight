<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;
use App\Models\User;

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

/*
 * Slice 4b: after too many taps the page stays, says how long to wait, and
 * never claims the tap worked (the redirect back would otherwise look like a
 * success to the button).
 */
it('asks a player who taps too often to wait, without leaving the page', function () {
    $profile = PlayerProfile::factory()->create();
    Event::factory()->for(Shop::factory()->create(['name' => 'Carta Alta']))->create();
    $this->actingAs($profile->user);
    $slip = '[data-slot="card"]';

    $page = visit(route('home'));

    // 30 taps a minute are allowed; each waits for its label before the next.
    foreach (range(1, 30) as $tap) {
        $page->click("{$slip} button")
            ->assertSeeIn($slip, $tap % 2 === 1 ? 'Going ✓' : "I'm going");
    }

    $page->click("{$slip} button")
        ->assertSee('Too many tries. Wait')
        ->assertSeeIn($slip, "I'm going")
        ->assertSeeIn($slip, '0 trainers going')
        ->assertPathIs('/');
});

/*
 * M08 AC4: a family taps "I'm going" and the picker opens with a checkbox per
 * trainer (a big toggle row); Save sends every choice in one request. Opening a sheet and ticking
 * boxes needs a real browser (the sheet's content is not in static markup).
 */
it('lets a family pick who is going, per trainer', function () {
    $parent = User::factory()->create();
    $ana = PlayerProfile::factory()->for($parent)->create(['name' => 'Ana']);
    $tiago = PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Tiago']);
    $event = Event::factory()->for(Shop::factory()->create(['name' => 'Carta Alta']))->create();
    $this->actingAs($parent);
    $slip = '[data-slot="card"]';

    $page = visit(route('home'));

    // Clicks wait for their target (up to 5s); assertSee checks once, at once.
    // So each sheet is first met with a click, and only then asserted on.
    $page->click("{$slip} button")
        ->click('Ana')
        ->assertSee('Going to Carta Alta?')
        ->click('Tiago')
        ->click('Save · 2 going')
        ->assertSee('Saved for Carta Alta')
        ->assertSeeIn($slip, 'Going (2)')
        ->assertSeeIn($slip, 'You: Ana, Tiago')
        ->assertSeeIn($slip, '2 trainers going');

    // Reopened, the sheet starts from the saved answers (both picked): unticking
    // Tiago leaves "Save · 1 going". Had it reopened empty, the tap would pick
    // Tiago instead, and the last check (only Ana saved) would fail.
    $page->click("{$slip} button")
        ->click('Tiago')
        ->click('Save · 1 going')
        ->assertSeeIn($slip, 'Going (1)')
        ->assertSeeIn($slip, 'You: Ana')
        ->assertSeeIn($slip, '1 trainers going');

    expect($event->attendances()->pluck('player_profile_id')->all())->toBe([$ana->id]);
});
