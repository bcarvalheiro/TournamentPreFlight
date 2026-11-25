<?php

use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M07 AC7: an account holds at most one profile aged 13 or older (the
 * holder's own; anyone else 13 or older signs up themselves) and at most 3
 * children. A fourth child needs an email to support (decisions.md, 24 Nov).
 */
beforeEach(function () {
    config(['app.support_email' => 'help@example.test']);
});

function adultProfile(): array
{
    return ['name' => 'Second Adult', 'pop_id' => '5555555', 'birth_year' => 1980];
}

function anotherChild(): array
{
    return ['name' => 'Fourth Child', 'pop_id' => '6666666', 'birth_year' => now('Europe/Lisbon')->year - 8, 'consent' => '1'];
}

it('refuses a second profile aged 13 or older', function () {
    $user = User::factory()->has(PlayerProfile::factory())->create();

    $this->actingAs($user)->post(route('profiles.store'), adultProfile())
        ->assertSessionHasErrors(['birth_year' => 'Your account already has a trainer aged 13 or older. Anyone else 13 or older signs up with their own account.']);

    expect($user->playerProfiles()->count())->toBe(1);
});

it('refuses a fourth child and points to support', function () {
    $user = User::factory()->create();
    PlayerProfile::factory()->child()->for($user)->count(3)->create();

    $this->actingAs($user)->post(route('profiles.store'), anotherChild())
        ->assertSessionHasErrors(['birth_year' => 'An account can hold up to 3 children. To add more, email us at help@example.test.']);

    expect($user->playerProfiles()->count())->toBe(3);
});

it('allows a child after the adult, and the adult after children', function () {
    $parent = User::factory()->has(PlayerProfile::factory())->create();
    $this->actingAs($parent)->post(route('profiles.store'), anotherChild())->assertSessionHasNoErrors();

    $nonPlayingParent = User::factory()->create();
    PlayerProfile::factory()->child()->for($nonPlayingParent)->count(3)->create();
    $this->actingAs($nonPlayingParent)->post(route('profiles.store'), adultProfile())->assertSessionHasNoErrors();

    expect($parent->playerProfiles()->count())->toBe(2)
        ->and($nonPlayingParent->playerProfiles()->count())->toBe(4);
});

it('never counts another account\'s profiles', function () {
    $other = User::factory()->has(PlayerProfile::factory())->create();
    PlayerProfile::factory()->child()->for($other)->count(3)->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), adultProfile())->assertSessionHasNoErrors();
    $this->actingAs($user)->post(route('profiles.store'), anotherChild())->assertSessionHasNoErrors();
});

it('tells the form what the account can still add', function (int $adults, int $children, bool $canAddAdult, bool $canAddChild) {
    $user = User::factory()->create();
    PlayerProfile::factory()->for($user)->count($adults)->create();
    PlayerProfile::factory()->child()->for($user)->count($children)->create();

    $this->actingAs($user)->get(route('profiles.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canAddAdult', $canAddAdult)
            ->where('canAddChild', $canAddChild)
            ->where('supportEmail', 'help@example.test'));
})->with([
    'empty account' => [0, 0, true, true],
    'adult only' => [1, 0, false, true],
    'three children' => [0, 3, true, false],
    'full' => [1, 3, false, false],
]);

it('stops counting a child who aged out on 1 January', function () {
    $user = User::factory()->create();
    PlayerProfile::factory()->child()->for($user)->count(2)->create();
    PlayerProfile::factory()->child()->for($user)->create(['birth_year' => now('Europe/Lisbon')->year - 13]);

    $this->travelTo(now('Europe/Lisbon')->addYear()->startOfYear()->addDay());

    $this->actingAs($user)->post(route('profiles.store'), anotherChild())->assertSessionHasNoErrors();
});

/*
 * Review fix (privacy): adding or editing trainers is throttled, so nobody can
 * try Pokemon Player IDs one after another to find which are taken, or claim
 * many quickly. Disputes go to support (decisions.md, 24 Nov).
 */
it('slows down an account that keeps adding or editing trainers', function () {
    $user = User::factory()->has(PlayerProfile::factory())->create();
    $profile = $user->playerProfiles()->sole();
    $this->actingAs($user);

    foreach (range(1, 10) as $attempt) {
        $this->post(route('profiles.store'), ['pop_id' => "77{$attempt}"])->assertRedirect();
    }

    // Too many tries: back to the page with the wait message (M08 Slice 4b), and nothing saved.
    $this->post(route('profiles.store'), ['pop_id' => '7799'])->assertRedirect()->assertInertiaFlash('toast.type', 'error');
    $this->put(route('profiles.update', $profile), ['pop_id' => '7799'])->assertRedirect()->assertInertiaFlash('toast.type', 'error');
    expect($user->playerProfiles()->count())->toBe(1)
        ->and($profile->fresh()->pop_id)->not->toBe('7799');
});
