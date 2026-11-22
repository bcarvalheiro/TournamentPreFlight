<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/*
 * M06 AC3: a new social identity gets an account only through the welcome
 * page and its "I am 13 or older" box. The identity waits in the session
 * meanwhile. Only an identity whose provider certifies for its email can sign
 * up; the email is the provider's and cannot be changed.
 */

/**
 * A pending sign-up as the callback stores it.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function pendingSignup(array $overrides = []): array
{
    return [
        'provider' => 'google',
        'provider_user_id' => 'id-789',
        'name' => 'Ana Lima',
        'email' => 'ana@example.test',
        ...$overrides,
    ];
}

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
    ])->and(User::query()->count())->toBe(0);
    $this->assertGuest();
})->with('new identities');

it('shows the name prefilled and the email the provider certified for', function () {
    $this->withSession(['social_signup' => pendingSignup()]);

    $this->get(route('welcome'))->assertInertia(fn (Assert $page) => $page
        ->component('auth/welcome')
        ->where('name', 'Ana Lima')
        ->where('email', 'ana@example.test'));
});

dataset('identities without a certified email', [
    'x without an email' => ['x', 'X', ['email' => null]],
    'discord unverified' => ['discord', 'Discord', ['verified' => false]],
    'google unverified' => ['google', 'Google', ['email_verified' => false]],
]);

it('refuses a sign-up without an email the provider certifies for', function (string $provider, string $name, array $identity) {
    Socialite::fake($provider, SocialiteUser::fake(['id' => 'id-789', 'name' => 'Ana Lima', 'email' => 'ana@example.test', ...$identity]));

    $this->get(route('social.callback', $provider))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => "Your {$name} account has no confirmed email. Sign in another way, or register with your email and a password."]);

    expect(session('social_signup'))->toBeNull()
        ->and(User::query()->count())->toBe(0);
    $this->assertGuest();
})->with('identities without a certified email');

it('sends anyone without a pending sign-up to the login page', function () {
    $this->get(route('welcome'))->assertRedirect(route('login'));
});

it('creates no account without the age confirmation', function () {
    $this->withSession(['social_signup' => pendingSignup()]);

    $this->post(route('welcome.store'), ['name' => 'Ana Lima'])
        ->assertSessionHasErrors(['age_confirmed' => 'Please confirm you are 13 or older.']);

    $this->assertGuest();
    expect(User::query()->count())->toBe(0);
});

it('creates the account, links the identity and returns to the intended page', function () {
    Notification::fake();
    $this->withSession(['social_signup' => pendingSignup(), 'url.intended' => url('/?type[]=cup')]);

    $this->post(route('welcome.store'), ['name' => 'Ana L.', 'age_confirmed' => '1'])
        ->assertRedirect(url('/?type[]=cup'));

    $user = User::query()->sole();
    expect($user)
        ->name->toBe('Ana L.')
        ->email->toBe('ana@example.test')
        ->password->toBeNull()
        ->email_verified_at->not->toBeNull()
        ->age_confirmed_at->not->toBeNull()
        ->and($user->socialAccounts()->sole())
        ->provider->value->toBe('google')
        ->provider_user_id->toBe('id-789')
        ->and(session('social_signup'))->toBeNull();
    $this->assertAuthenticatedAs($user);
    Notification::assertNothingSent();
});

it('keeps the email the provider certified for', function () {
    $this->withSession(['social_signup' => pendingSignup()]);

    $this->post(route('welcome.store'), ['name' => 'Ana Lima', 'email' => 'other@example.test', 'age_confirmed' => '1']);

    expect(User::query()->sole()->email)->toBe('ana@example.test');
});

it('creates nothing without a pending sign-up', function () {
    $this->post(route('welcome.store'), ['name' => 'Ana Lima', 'age_confirmed' => '1'])
        ->assertRedirect(route('login'));

    expect(User::query()->count())->toBe(0);
});
