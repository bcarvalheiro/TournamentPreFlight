<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/*
 * M06 review: an account belongs to whoever proves its email. Anything set up
 * on it before the email was proven (a linked social identity, a signed-in
 * session, a "remember me" token) stops working once the owner proves it,
 * by a provider that certifies for the email or by a password reset.
 */

/**
 * An unverified account someone else claimed: their X identity is linked and
 * their session is open.
 */
function claimedAccount(array $attributes = []): User
{
    $user = User::factory()->unverified()->create(['email' => 'ana@example.test', 'remember_token' => 'claimers-token', ...$attributes]);
    SocialAccount::factory()->for($user)->create(['provider' => 'x', 'provider_user_id' => 'claimers-x']);
    DB::table('sessions')->insert([
        'id' => 'claimers-session', 'user_id' => $user->id, 'ip_address' => null,
        'user_agent' => null, 'payload' => '', 'last_activity' => time(),
    ]);

    return $user;
}

it('evicts the claimer when the owner signs in with a provider that certifies for the email', function () {
    $user = claimedAccount();
    Socialite::fake('google', SocialiteUser::fake(['id' => 'owners-google', 'email' => 'ana@example.test', 'email_verified' => true]));

    $this->get(route('social.callback', 'google'));

    $user->refresh();
    expect($user->socialAccounts()->pluck('provider_user_id')->all())->toBe(['owners-google'])
        ->and(DB::table('sessions')->where('id', 'claimers-session')->exists())->toBeFalse()
        ->and($user->remember_token)->not->toBe('claimers-token')
        ->and($user->password)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('evicts the claimer when the owner resets the password', function () {
    $user = claimedAccount();

    $this->post(route('password.update'), [
        'token' => Password::createToken($user),
        'email' => 'ana@example.test',
        'password' => 'owners-password',
        'password_confirmation' => 'owners-password',
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->socialAccounts()->count())->toBe(0)
        ->and(DB::table('sessions')->where('id', 'claimers-session')->exists())->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('leaves the linked logins of a verified account alone on a password reset', function () {
    $user = claimedAccount(['email_verified_at' => now()]);

    $this->post(route('password.update'), [
        'token' => Password::createToken($user),
        'email' => 'ana@example.test',
        'password' => 'owners-password',
        'password_confirmation' => 'owners-password',
    ]);

    expect($user->socialAccounts()->count())->toBe(1);
});
