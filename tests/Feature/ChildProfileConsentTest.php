<?php

use App\Models\PlayerProfile;
use App\Models\User;

/*
 * M07 AC3: a child profile (the person turns 13 or younger this Lisbon year)
 * needs the parent's consent box. With it, the time and the consenting
 * account (the owner) are stored. A parent who does not play may hold only
 * their child's profile (decisions.md, 23 Nov).
 */
function childProfile(array $overrides = []): array
{
    return [
        'name' => 'Young Trainer',
        'pop_id' => '7654321',
        'birth_year' => now('Europe/Lisbon')->year - 10,
        ...$overrides,
    ];
}

it('refuses a child profile without consent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), childProfile())
        ->assertSessionHasErrors(['consent' => 'A parent must give consent for a player under 13.']);

    expect(PlayerProfile::query()->count())->toBe(0);
});

it('saves a child profile with consent, recording when and by whom', function () {
    $this->freezeSecond(); // MySQL keeps whole seconds
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), childProfile(['consent' => '1']))
        ->assertRedirect(route('home'));

    $profile = $user->playerProfiles()->sole();
    expect($profile->is_child)->toBeTrue()
        ->and($profile->consent_given_at->equalTo(now()))->toBeTrue()
        ->and($profile->consent_by_user_id)->toBe($user->id);
});

it('asks consent for someone turning 13 this year, not 14', function (int $age, bool $needsConsent) {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('profiles.store'), childProfile([
        'birth_year' => now('Europe/Lisbon')->year - $age,
    ]));

    $needsConsent
        ? $response->assertSessionHasErrors('consent')
        : $response->assertSessionHasNoErrors();
})->with([
    'turning 13' => [13, true],
    'turning 14' => [14, false],
]);

it('stores no consent for an adult who ticks the box', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), childProfile(['birth_year' => 1990, 'consent' => '1']));

    $profile = $user->playerProfiles()->sole();
    expect($profile->consent_given_at)->toBeNull()
        ->and($profile->consent_by_user_id)->toBeNull();
});

it('shows the consent error together with the other field errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), childProfile(['name' => '']))
        ->assertSessionHasErrors(['name', 'consent']);
});
