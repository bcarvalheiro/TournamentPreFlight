<?php

use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;

/*
 * M09 AC6: opening a shared `?going` link shows the "Going to …?" pop-up with
 * the headcount and the answer controls; only a tap there marks going. An
 * ended event shows no pop-up. Opening a dialog on load and tapping in it
 * need a real browser.
 */
beforeEach(function () {
    $this->event = Event::factory()
        ->for(Shop::factory()->create(['name' => 'Carta Alta']))
        ->create(['title' => 'Winter Cup']);
    $this->url = "/events/{$this->event->id}-winter-cup";
});

it('asks a player "Going to …?" and saves only on their tap', function () {
    $profile = PlayerProfile::factory()->create(['name' => 'Ana']);
    $this->actingAs($profile->user);

    $page = visit($this->url.'?going')
        ->assertSee('Going to Carta Alta?')
        ->assertSee('0 trainers going');

    // Opening the link wrote nothing.
    expect($this->event->attendances()->count())->toBe(0);

    $page->click('[role="dialog"] button[aria-pressed]')
        ->assertSee("You're going to Carta Alta");

    expect($this->event->attendances()->pluck('player_profile_id')->all())->toBe([$profile->id]);
});

it('shows no focus ring on a trainer row until the keyboard is used', function () {
    // Bruno, 25 Nov: the pop-up opens by itself, and the dialog moved focus to the
    // first trainer on opening, which the browser treats as visible focus: the
    // first row wore the red focus ring though nobody had used the keyboard.
    $parent = User::factory()->create();
    PlayerProfile::factory()->for($parent)->create(['name' => 'Ana']);
    PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Tiago']);
    $this->actingAs($parent);

    $page = visit($this->url.'?going')->assertSee('Going to Carta Alta?');

    // Opened by the page, not by a key: no row may wear the focus ring yet.
    $page->assertScript('document.querySelector("[role=dialog] button[aria-pressed]:focus-visible") === null');

    $page->click('[role="dialog"] button:has-text("Tiago")')
        ->assertSee('Tiago is going to Carta Alta')
        ->assertScript('document.querySelector("[role=dialog] button[aria-pressed]:focus-visible") === null');
});

it('sends a guest from the pop-up to sign in', function () {
    visit($this->url.'?going')
        ->click('[role="dialog"] button[type="submit"]')
        ->assertSee("Sign in to say you're going.");
});

it('shows no pop-up on an ended event', function () {
    $this->event->update(['starts_at' => Carbon::now()->subDay()]);

    visit($this->url.'?going')
        ->assertSee('This event has ended')
        ->assertDontSee('Going to Carta Alta?');
});
