<?php

use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M07 AC1-AC3: the owner edits a trainer; anyone else gets 403. The Pokemon
 * Player ID stays unique but may keep its own value; a trainer who becomes a
 * child needs consent, an existing child keeps theirs; the account limits
 * (one trainer aged 13 or older, three children) hold when a birth year moves
 * a trainer across (decisions.md, 24 Nov).
 */
function childYear(): int
{
    return now('Europe/Lisbon')->year - 9;
}

it('lets the owner edit a trainer', function () {
    $profile = PlayerProfile::factory()->create(['pop_id' => '1111111']);

    $this->actingAs($profile->user)
        ->put(route('profiles.update', $profile), ['name' => 'New Name', 'pop_id' => '2222222', 'birth_year' => 1988])
        ->assertRedirect(route('profiles.index'));

    expect($profile->fresh())
        ->name->toBe('New Name')
        ->pop_id->toBe('2222222')
        ->birth_year->toBe(1988);
});

it('refuses another account, on the page and on save', function () {
    $profile = PlayerProfile::factory()->create(['name' => 'Kept Name']);
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());

    $this->get(route('profiles.edit', $profile))->assertForbidden();
    $this->put(route('profiles.update', $profile), ['name' => 'Taken', 'pop_id' => '3333333', 'birth_year' => 1990])
        ->assertForbidden();

    expect($profile->fresh()->name)->toBe('Kept Name');
});

it('keeps its own Pokemon Player ID but not someone else\'s', function () {
    PlayerProfile::factory()->create(['pop_id' => '9999999']);
    $profile = PlayerProfile::factory()->create(['pop_id' => '1111111', 'birth_year' => 1990]);
    $this->actingAs($profile->user);

    $this->put(route('profiles.update', $profile), ['name' => 'Same ID', 'pop_id' => '1111111', 'birth_year' => 1990])
        ->assertSessionHasNoErrors();
    $this->put(route('profiles.update', $profile), ['name' => 'Same ID', 'pop_id' => '9999999', 'birth_year' => 1990])
        ->assertSessionHasErrors(['pop_id' => 'This Pokemon Player ID (POP ID) is already on another profile.']);
});

it('asks consent when a trainer becomes a child', function () {
    $this->freezeSecond();
    $owner = User::factory()->create();
    $profile = PlayerProfile::factory()->for($owner)->create(['birth_year' => 1990]);
    $this->actingAs($owner);
    $asChild = ['name' => $profile->name, 'pop_id' => $profile->pop_id, 'birth_year' => childYear()];

    $this->put(route('profiles.update', $profile), $asChild)
        ->assertSessionHasErrors(['consent' => 'A parent must give consent for a trainer under 13.']);
    $this->put(route('profiles.update', $profile), [...$asChild, 'consent' => '1'])
        ->assertSessionHasNoErrors();

    expect($profile->fresh())
        ->is_child->toBeTrue()
        ->consent_by_user_id->toBe($owner->id)
        ->and($profile->fresh()->consent_given_at->equalTo(now()))->toBeTrue();
});

it('keeps an existing child\'s consent without asking again', function () {
    $child = PlayerProfile::factory()->child()->create();
    $consentGivenAt = $child->consent_given_at;

    $this->actingAs($child->user)
        ->put(route('profiles.update', $child), ['name' => 'Renamed Child', 'pop_id' => $child->pop_id, 'birth_year' => $child->birth_year])
        ->assertSessionHasNoErrors();

    expect($child->fresh())
        ->name->toBe('Renamed Child')
        ->consent_given_at->toEqual($consentGivenAt);
});

it('refuses a child turning into a second trainer aged 13 or older', function () {
    $owner = User::factory()->has(PlayerProfile::factory())->create();
    $child = PlayerProfile::factory()->child()->for($owner)->create();

    $this->actingAs($owner)
        ->put(route('profiles.update', $child), ['name' => $child->name, 'pop_id' => $child->pop_id, 'birth_year' => 1990])
        ->assertSessionHasErrors(['birth_year' => 'Your account already has a trainer aged 13 or older. Anyone else 13 or older signs up with their own account.']);
});

it('refuses the adult turning into a fourth child', function () {
    config(['app.support_email' => 'help@example.test']);
    $owner = User::factory()->create();
    $adult = PlayerProfile::factory()->for($owner)->create();
    PlayerProfile::factory()->child()->for($owner)->count(3)->create();

    $this->actingAs($owner)
        ->put(route('profiles.update', $adult), ['name' => $adult->name, 'pop_id' => $adult->pop_id, 'birth_year' => childYear(), 'consent' => '1'])
        ->assertSessionHasErrors(['birth_year' => 'An account can hold up to 3 children. To add more, email us at help@example.test.']);
});

it('opens the edit page with the trainer\'s data', function () {
    $child = PlayerProfile::factory()->child()->create(['name' => 'Rui', 'pop_id' => '4444444']);

    $this->actingAs($child->user)->get(route('profiles.edit', $child))
        ->assertInertia(fn (Assert $page) => $page
            ->component('profiles/edit')
            ->where('profile.id', $child->id)
            ->where('profile.name', 'Rui')
            ->where('profile.popId', '4444444')
            ->where('profile.isChild', true)
            ->where('consentFromYear', now('Europe/Lisbon')->year - 13));
});

/*
 * Review fix: is_child is only refreshed when a row is saved, so on 1 January
 * a child who turns 14 still has is_child = true. Edits and limits decide from
 * the birth year today instead.
 */
it('lets the owner edit a child who aged out on 1 January', function () {
    $owner = User::factory()->has(PlayerProfile::factory())->create();
    $child = PlayerProfile::factory()->child()->for($owner)->create(['birth_year' => now('Europe/Lisbon')->year - 13]);

    $this->travelTo(now('Europe/Lisbon')->addYear()->startOfYear()->addDay());

    $this->actingAs($owner)
        ->put(route('profiles.update', $child), ['name' => 'Typo Fixed', 'pop_id' => $child->pop_id, 'birth_year' => $child->birth_year])
        ->assertSessionHasNoErrors();

    expect($child->fresh()->name)->toBe('Typo Fixed');
});
