<?php

use App\Models\PlayerProfile;
use App\Models\User;

/*
 * M06 AC2: a signed-in user uses the site only with a verified email, a
 * password and a player profile, so only players answer. Auth pages, settings
 * and logout always stay open; guests are not affected.
 */
it('lets guests browse', function () {
    $this->get(route('home'))->assertOk();
});

it('sends an unverified user to the verify notice', function () {
    $this->actingAs(User::factory()->unverified()->has(PlayerProfile::factory())->create());

    $this->get(route('home'))->assertRedirect(route('verification.notice'));
});

it('sends a user without a profile to create one', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))->assertRedirect(route('profiles.create'));
    $this->get(route('profiles.create'))->assertOk();
});

it('sends a user without a password to the profile page', function () {
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create(['password' => null]));

    $this->get(route('home'))->assertRedirect(route('profiles.create'));
});

it('lets a ready player in', function () {
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());

    $this->get(route('home'))->assertOk();
});

it('keeps settings and logout open to a user who is not ready', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
    $this->post(route('logout'));

    $this->assertGuest();
});
