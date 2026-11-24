<?php

use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M07: every page knows how many trainers (player profiles) the signed-in
 * account holds, for the navbar's "Trainers (n)" and the no-profile notice.
 */
it('shares no count with a guest', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.profilesCount', null));
});

it('shares the account\'s own count, not other accounts\'', function () {
    PlayerProfile::factory()->create();
    $user = User::factory()->has(PlayerProfile::factory())->create();
    PlayerProfile::factory()->child()->for($user)->create();

    $this->actingAs($user)->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.profilesCount', 2));
});

it('shares zero with an account that has no trainers yet', function () {
    $this->actingAs(User::factory()->create())->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.profilesCount', 0));
});
