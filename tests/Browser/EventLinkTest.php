<?php

use App\Models\Event;
use App\Models\Shop;

/*
 * M09 Slice 4: a slip and an "Up next" row open the event page from anywhere
 * but their count-and-button end (the shop name's link is stretched over the
 * row); that end, with the quick "I'm going" button, sits above the link's
 * layer and keeps its own taps. Which layer
 * gets a tap only shows in a real browser.
 */
beforeEach(function () {
    $this->event = Event::factory()
        ->for(Shop::factory()->create(['name' => 'Carta Alta']))
        ->create(['title' => 'Winter Cup']);
});

it('opens the event page from the left part of a slip', function () {
    // The centre of the agenda's slip (a Card), not the shop name itself.
    visit(route('home'))
        ->click('[data-slot="card"]')
        ->assertPathIs("/events/{$this->event->id}-winter-cup");
});

it('leaves the trainers count on a slip alone', function () {
    // Bruno, 25 Nov: on a phone the count sits next to the button, so a tap
    // there must not open the event page.
    visit(route('home'))
        ->click('[data-test="slip-count"]')
        ->assertPathIs('/');
});

it('opens the event page from the left part of an "Up next" row', function () {
    visit(route('home'))
        ->click('[data-test="up-next-row"]')
        ->assertPathIs("/events/{$this->event->id}-winter-cup");
});

it('leaves the count and button end of an "Up next" row alone', function () {
    visit(route('home'))
        ->click('[data-test="up-next-count"]')
        ->assertPathIs('/');
});

it('keeps the "I\'m going" button its own tap', function () {
    // A guest's tap goes to sign in (M06 pending intent), not to the event page.
    visit(route('home'))
        ->click('[data-slot="card"] button')
        ->assertPathIs('/login');
});
