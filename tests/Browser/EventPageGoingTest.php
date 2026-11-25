<?php

use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;

/*
 * M09 AC2 and Slice 5: on the event page a player's trainers are toggle rows
 * in "Who's heading there", and each tap saves (a partial reload and a toast);
 * anyone else's "I'm going" signs in and comes back here; an ended event has
 * no controls; phones get a bar that jumps to the card. Taps, partial reloads,
 * toasts and the sign-in round trip need a real browser.
 */
beforeEach(function () {
    $this->event = Event::factory()
        ->for(Shop::factory()->create(['name' => 'Carta Alta']))
        ->create(['title' => 'Winter Cup']);
    $this->url = "/events/{$this->event->id}-winter-cup";
});

/** The count card ("Who's heading there") and its big number. */
const COUNTS = '[data-test="event-counts"]';
const COUNT = '[data-test="going-count"]';

it('lets a solo player tap their row to go, then tap again to stop', function () {
    $profile = PlayerProfile::factory()->create(['name' => 'Ana']);
    $this->actingAs($profile->user);

    $page = visit($this->url)
        ->assertSeeIn(COUNT, '0')
        ->click('Ana')
        ->assertSee("You're going to Carta Alta")
        ->assertSeeIn(COUNTS, 'Going ✓')
        ->assertSeeIn(COUNT, '1');

    expect($this->event->attendances()->pluck('player_profile_id')->all())->toBe([$profile->id]);

    $page->click('Ana')
        ->assertSee("You're no longer going to Carta Alta")
        ->assertSeeIn(COUNT, '0');

    expect($this->event->attendances()->count())->toBe(0);
});

it('saves only the trainer a family taps', function () {
    $parent = User::factory()->create();
    $ana = PlayerProfile::factory()->for($parent)->create(['name' => 'Ana']);
    PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Tiago']);
    $this->actingAs($parent);

    visit($this->url)
        ->click('Ana')
        ->assertSee('Ana is going to Carta Alta')
        ->assertSeeIn(COUNT, '1');

    expect($this->event->attendances()->pluck('player_profile_id')->all())->toBe([$ana->id]);
});

it('shows no controls on an ended event', function () {
    $this->event->update(['starts_at' => Carbon::now()->subDay()]);
    $this->actingAs(PlayerProfile::factory()->create(['name' => 'Ana'])->user);

    visit($this->url)
        ->assertSee('This event has ended')
        ->assertDontSeeIn(COUNTS, 'Ana')
        // Not assertDontSee("I'm going"): the disclaimer quotes those words.
        ->assertMissing(COUNTS.' button')
        ->assertMissing('[data-test="going-bar"]');
});

it('signs a guest in and sets a solo player going on this page', function () {
    $profile = PlayerProfile::factory()->create();

    visit($this->url)
        ->click(COUNTS.' button')
        ->assertSee("Sign in to say you're going.")
        ->fill('email', $profile->user->email)
        ->fill('password', 'password')
        ->click('[data-test="login-button"]')
        ->assertPathIs($this->url)
        ->assertSee("You're going to Carta Alta")
        ->assertSeeIn(COUNT, '1');

    expect($this->event->attendances()->pluck('player_profile_id')->all())->toBe([$profile->id]);
});

it('gives phones a bar that jumps to the count card', function () {
    visit($this->url)
        ->resize(390, 844)
        ->assertVisible('[data-test="going-bar"]')
        ->assertAttribute('[data-test="going-bar"] a', 'href', '#going');
});
