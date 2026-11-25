<?php

use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\Shop;
use App\Models\User;

/*
 * M08 AC5: a signed-out player taps "I'm going" on a slip, signs in with email
 * and password, and lands back on the home page where the tap carries on: a
 * solo account's answer is applied, a family gets the picker open (each tap
 * there saves, M09 Slice 5b). The whole
 * sign-in round trip needs a real browser.
 */
function tapGoingThenSignIn(User $user): mixed
{
    $page = visit(route('home'));

    // The agenda's slip (a Card), not the pairings sheet.
    return $page->click('[data-slot="card"] button')
        ->assertSee("Sign in to say you're going.")
        ->fill('email', $user->email)
        ->fill('password', 'password')
        // The form's button: the navbar also has a "Sign in" link.
        ->click('[data-test="login-button"]')
        ->assertPathIs('/');
}

it('applies a solo account\'s answer after signing in', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->for(Shop::factory()->create(['name' => 'Carta Alta']))->create();

    tapGoingThenSignIn($profile->user)
        ->assertSee("You're going to Carta Alta")
        ->assertSeeIn('[data-slot="card"]', 'Going ✓')
        ->assertSeeIn('[data-slot="card"]', '1 trainers going');

    expect($event->attendances()->pluck('player_profile_id')->all())->toBe([$profile->id]);
});

it('opens the picker for a family after signing in', function () {
    $parent = User::factory()->create();
    $ana = PlayerProfile::factory()->for($parent)->create(['name' => 'Ana']);
    PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Tiago']);
    $event = Event::factory()->for(Shop::factory()->create(['name' => 'Carta Alta']))->create();

    $page = tapGoingThenSignIn($parent);

    // Nothing is saved until the family chooses.
    expect($event->attendances()->count())->toBe(0);

    // The click waits for the sheet to open (assertSee would not wait); the tap saves Ana at once.
    $page->click('Ana')
        ->assertSee('Going to Carta Alta?')
        ->assertSee('Ana is going to Carta Alta')
        ->assertSeeIn('[data-slot="card"]', 'Going (1)');

    expect($event->attendances()->pluck('player_profile_id')->all())->toBe([$ana->id]);
});
