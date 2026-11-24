<?php

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/*
 * One account (a login) holds one or more player profiles, for example a
 * parent's own and one per child. A Pokemon Player ID (POP ID) belongs to one profile in the
 * whole app (AC2).
 */
it('refuses a Pokemon Player ID (POP ID) already used by another account', function () {
    PlayerProfile::factory()->for(User::factory())->create(['pop_id' => '1234567']);

    expect(fn () => PlayerProfile::factory()->for(User::factory())->create(['pop_id' => '1234567']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('lets one account hold several profiles', function () {
    $parent = User::factory()->create();
    PlayerProfile::factory()->count(3)->for($parent)->create();

    expect($parent->playerProfiles)->toHaveCount(3);
});

it('deletes the profiles when the account is deleted', function () {
    $parent = User::factory()->create();
    PlayerProfile::factory()->count(2)->for($parent)->create();

    $parent->delete();

    expect(PlayerProfile::query()->count())->toBe(0);
});
