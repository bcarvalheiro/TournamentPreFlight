<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/*
 * M06 AC3 and AC7: a new social identity gets an account only through the
 * welcome page and its "I am 13 or older" box. The identity waits in the
 * session meanwhile. An email the provider vouches for is fixed; any other
 * email is typed and verified by email.
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
        'email_verified' => true,
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

it('keeps the email the provider vouched for', function () {
    $this->withSession(['social_signup' => pendingSignup()]);

    $this->post(route('welcome.store'), ['name' => 'Ana Lima', 'email' => 'other@example.test', 'age_confirmed' => '1']);

    expect(User::query()->sole()->email)->toBe('ana@example.test');
});

it('verifies a typed email by email', function () {
    Notification::fake();
    $this->withSession(['social_signup' => pendingSignup(['provider' => 'x', 'email' => null, 'email_verified' => false])]);

    $this->post(route('welcome.store'), ['name' => 'Ana Lima', 'email' => 'ana@example.test', 'age_confirmed' => '1']);

    $user = User::query()->sole();
    expect($user->email_verified_at)->toBeNull();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('creates nothing without a pending sign-up', function () {
    $this->post(route('welcome.store'), ['name' => 'Ana Lima', 'age_confirmed' => '1'])
        ->assertRedirect(route('login'));

    expect(User::query()->count())->toBe(0);
});
