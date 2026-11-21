<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/*
 * M06 AC3 and AC7: a new social identity gets an account only through the
 * welcome page and its "I am 13 or older" box. The identity waits in the
 * session meanwhile. An email the provider vouches for is fixed; any other
 * email is typed and verified by email.
 */
dataset('new identities', [
    'google' => ['google', ['email_verified' => true]],
    'discord' => ['discord', ['verified' => true]],
    'x' => ['x', []],
]);

it('keeps a new identity in the session and sends it to the welcome page', function (string $provider, array $verified) {
    Socialite::fake($provider, SocialiteUser::fake(['id' => 'id-789', 'name' => 'Ana Lima', 'email' => 'ana@example.test', ...$verified]));

    $this->get(route('social.callback', $provider))->assertRedirect(route('welcome'));

    expect(session('social_signup'))->toBe([
        'provider' => $provider,
        'provider_user_id' => 'id-789',
        'name' => 'Ana Lima',
        'email' => 'ana@example.test',
        'email_verified' => true,
    ])->and(User::query()->count())->toBe(0);
    $this->assertGuest();
})->with('new identities');

it('shows the name prefilled and fixes an email the provider vouched for', function () {
    $this->withSession(['social_signup' => [
        'provider' => 'google', 'provider_user_id' => 'id-789', 'name' => 'Ana Lima',
        'email' => 'ana@example.test', 'email_verified' => true,
    ]]);

    $this->get(route('welcome'))->assertInertia(fn (Assert $page) => $page
        ->component('auth/welcome')
        ->where('name', 'Ana Lima')
        ->where('email', 'ana@example.test')
        ->where('emailLocked', true));
});

it('asks for an email when the provider sent none', function () {
    Socialite::fake('x', SocialiteUser::fake(['id' => 'id-789', 'name' => 'Ana Lima', 'email' => null]));

    $this->get(route('social.callback', 'x'));

    $this->get(route('welcome'))->assertInertia(fn (Assert $page) => $page
        ->where('email', null)
        ->where('emailLocked', false));
});

it('sends anyone without a pending sign-up to the login page', function () {
    $this->get(route('welcome'))->assertRedirect(route('login'));
});
