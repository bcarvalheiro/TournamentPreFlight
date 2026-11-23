<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/*
 * M06 review: an account belongs to whoever proves its email. Someone who
 * registered another person's email with a password stays signed in until the
 * owner proves the email, by a provider that vouches for it or by a password
 * reset; then their session and "remember me" token stop working.
 */

/**
 * An unverified account someone else registered with a password, with their
 * session still open.
 */
function claimedAccount(): User
{
    $user = User::factory()->unverified()->create(['email' => 'ana@example.test', 'remember_token' => 'claimers-token']);
    DB::table('sessions')->insert([
        'id' => 'claimers-session', 'user_id' => $user->id, 'ip_address' => null,
        'user_agent' => null, 'payload' => '', 'last_activity' => time(),
    ]);

    return $user;
}

it('signs the claimer out when the owner signs in with a provider that vouches for the email', function () {
    $user = claimedAccount();
    Socialite::fake('google', SocialiteUser::fake(['id' => 'owners-google', 'email' => 'ana@example.test', 'email_verified' => true]));

    $this->get(route('social.callback', 'google'));

    $user->refresh();
    expect(DB::table('sessions')->where('id', 'claimers-session')->exists())->toBeFalse()
        ->and($user->remember_token)->not->toBe('claimers-token')
        ->and($user->password)->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('signs the claimer out when the owner resets the password', function () {
    $user = claimedAccount();

    $this->post(route('password.update'), [
        'token' => Password::createToken($user),
        'email' => 'ana@example.test',
        'password' => 'owners-password',
        'password_confirmation' => 'owners-password',
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect(DB::table('sessions')->where('id', 'claimers-session')->exists())->toBeFalse()
        ->and($user->hasVerifiedEmail())->toBeTrue();
});
