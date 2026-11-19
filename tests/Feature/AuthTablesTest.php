<?php

use App\Models\MagicLoginToken;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/*
 * The tables sign-in needs (M06, M13). One provider identity belongs to one
 * account (AC2), and one magic link token opens one login.
 */
it('refuses the same provider identity on two accounts', function () {
    SocialAccount::factory()->for(User::factory())->create(['provider' => 'google', 'provider_user_id' => '104857600']);

    expect(fn () => SocialAccount::factory()->for(User::factory())->create(['provider' => 'google', 'provider_user_id' => '104857600']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows the same provider user id on different providers', function () {
    SocialAccount::factory()->create(['provider' => 'google', 'provider_user_id' => '104857600']);
    SocialAccount::factory()->create(['provider' => 'discord', 'provider_user_id' => '104857600']);

    expect(SocialAccount::query()->where('provider_user_id', '104857600')->count())->toBe(2);
});

it('deletes the linked logins when the account is deleted', function () {
    $user = User::factory()->create();
    SocialAccount::factory()->for($user)->create();

    $user->delete();

    expect(SocialAccount::query()->count())->toBe(0);
});

it('refuses two magic link tokens with the same hash', function () {
    $hash = hash('sha256', 'the-token-in-the-link');
    MagicLoginToken::factory()->create(['token_hash' => $hash]);

    expect(fn () => MagicLoginToken::factory()->create(['token_hash' => $hash]))
        ->toThrow(UniqueConstraintViolationException::class);
});
