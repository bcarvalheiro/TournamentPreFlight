<?php

use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M07 AC1: an account sees its own player profiles, oldest first, and never
 * another account's. Each one is sent as App\Data\Private\OwnProfileData.
 */
it('lists the account\'s own profiles, oldest first', function () {
    $parent = User::factory()->create();
    $adult = PlayerProfile::factory()->for($parent)->create(['name' => 'Ana Silva', 'pop_id' => '1111111', 'birth_year' => 1985]);
    $child = PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Rui Silva', 'pop_id' => '2222222']);

    $this->actingAs($parent)->get(route('profiles.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('profiles/index')
            ->has('profiles', 2)
            ->where('profiles.0', [
                'id' => $adult->id,
                'name' => 'Ana Silva',
                'popId' => '1111111',
                'birthYear' => 1985,
                'isChild' => false,
            ])
            ->where('profiles.1.id', $child->id)
            ->where('profiles.1.isChild', true));
});

it('never lists another account\'s profiles', function () {
    PlayerProfile::factory()->create();
    $user = User::factory()->has(PlayerProfile::factory())->create();

    $this->actingAs($user)->get(route('profiles.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('profiles', 1)
            ->where('profiles.0.id', $user->playerProfiles()->sole()->id));
});

it('shows an empty list to an account without profiles', function () {
    $this->actingAs(User::factory()->create())->get(route('profiles.index'))
        ->assertInertia(fn (Assert $page) => $page->has('profiles', 0));
});

it('sends a guest to sign in and an unverified account to verify', function () {
    $this->get(route('profiles.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('profiles.index'))
        ->assertRedirect(route('verification.notice'));
});

it('no longer shows a child who aged out on 1 January as a child', function () {
    $child = PlayerProfile::factory()->child()->create(['birth_year' => now('Europe/Lisbon')->year - 13]);

    $this->travelTo(now('Europe/Lisbon')->addYear()->startOfYear()->addDay());

    $this->actingAs($child->user)->get(route('profiles.index'))
        ->assertInertia(fn (Assert $page) => $page->where('profiles.0.isChild', false));
});
