<?php

use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
 * Slice 4b (Bruno, 25 Nov): any rate limit on a save sends the player back to
 * the page with one message saying how long to wait, instead of the bare 429
 * page in Inertia's modal. Time is frozen, so Laravel's Retry-After is exactly
 * the 60 seconds of a one-minute limit.
 */
beforeEach(function () {
    $this->freezeTime();
});

it('sends the 31st save of a minute back to the page with the wait message', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();
    $this->actingAs($profile->user)->from(route('home'));

    // The going button's limit: 30 saves a minute (Slice 1).
    foreach (range(1, 30) as $tap) {
        $this->put(route('events.attendance.update', $event), ['profiles' => [$profile->id => $tap % 2 === 1]])
            ->assertRedirect(route('home'))
            ->assertInertiaFlashMissing('toast');
    }

    $this->put(route('events.attendance.update', $event), ['profiles' => [$profile->id => true]])
        ->assertRedirect(route('home'))
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Too many tries. Wait 60 seconds and try again.']);

    // 30 taps: going, not going, ... ending on not going; the 31st saved nothing.
    expect($profile->attendances()->count())->toBe(0);
});

it('uses the same message for any other limited form', function () {
    $this->actingAs(User::factory()->create())->from(route('profiles.create'));

    // Adding a trainer: 10 a minute (M07). Empty posts are enough; the limit comes first.
    foreach (range(1, 10) as $try) {
        $this->post(route('profiles.store'))->assertRedirect(route('profiles.create'));
    }

    $this->post(route('profiles.store'))
        ->assertRedirect(route('profiles.create'))
        ->assertInertiaFlash('toast.message', 'Too many tries. Wait 60 seconds and try again.');
});

it('keeps the 429 answer for a JSON caller', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();
    $this->actingAs($profile->user);

    foreach (range(1, 30) as $tap) {
        $this->putJson(route('events.attendance.update', $event), ['profiles' => [$profile->id => true]]);
    }

    // A JSON caller (a script, a future API) has no page to go back to.
    $this->putJson(route('events.attendance.update', $event), ['profiles' => [$profile->id => true]])
        ->assertTooManyRequests();
});

it('keeps the 429 page for a page load', function () {
    // No real page is rate-limited today, so the test adds one. Going back from a
    // page load could loop to the same URL, so the handler leaves page loads alone.
    Route::get('limited-page', fn () => 'ok')->middleware(['web', 'throttle:1,1']);
    $this->actingAs(User::factory()->create());

    $this->get('limited-page')->assertOk();
    $this->get('limited-page')->assertTooManyRequests();
});
