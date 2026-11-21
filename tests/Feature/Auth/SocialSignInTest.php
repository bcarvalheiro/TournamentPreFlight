<?php

use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use SocialiteProviders\Discord\Provider as DiscordProvider;

/*
 * M06 AC3: Google, Discord and X sign in an account that already exists. A
 * known identity signs in; an unknown one links to an account with the same
 * email only when the provider says the email is verified. Socialite is faked:
 * the extra keys passed to User::fake() become the provider's raw data, which
 * is where Google (email_verified) and Discord (verified) say so. X only ever
 * sends a confirmed email.
 */
dataset('providers', [
    'google' => ['google', ['email_verified' => true]],
    'discord' => ['discord', ['verified' => true]],
    'x' => ['x', []],
]);

dataset('providers that flag unverified emails', [
    'google' => ['google', ['email_verified' => false]],
    'discord' => ['discord', ['verified' => false]],
]);

it('sends the player to the provider', function (string $provider) {
    Socialite::fake($provider);

    $this->get(route('social.redirect', $provider))->assertRedirect();
})->with('providers');

it('signs in a known identity', function (string $provider) {
    $user = User::factory()->create();
    SocialAccount::factory()->for($user)->create(['provider' => $provider, 'provider_user_id' => 'id-123']);
    Socialite::fake($provider, SocialiteUser::fake(['id' => 'id-123', 'email' => 'someone-else@example.test']));

    $this->get(route('social.callback', $provider))->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
})->with('providers');

it('links a verified email to the existing account', function (string $provider, array $verified) {
    $user = User::factory()->create(['email' => 'ana@example.test']);
    Socialite::fake($provider, SocialiteUser::fake(['id' => 'id-456', 'email' => 'ana@example.test', ...$verified]));

    $this->get(route('social.callback', $provider))->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
    expect($user->socialAccounts()->sole())
        ->provider->value->toBe($provider)
        ->provider_user_id->toBe('id-456')
        ->provider_email->toBe('ana@example.test');
})->with('providers');

it('verifies an unverified account it links to and clears its password', function (string $provider, array $verified) {
    $user = User::factory()->unverified()->create(['email' => 'ana@example.test']);
    Socialite::fake($provider, SocialiteUser::fake(['id' => 'id-456', 'email' => 'ana@example.test', ...$verified]));

    $this->get(route('social.callback', $provider));

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->toBeNull();
})->with('providers');

it('never links an email the provider has not verified', function (string $provider, array $unverified) {
    User::factory()->create(['email' => 'ana@example.test']);
    Socialite::fake($provider, SocialiteUser::fake(['id' => 'id-456', 'email' => 'ana@example.test', ...$unverified]));

    $this->get(route('social.callback', $provider))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'An account with this email already exists. Sign in with your email and password.']);

    $this->assertGuest();
    expect(SocialAccount::query()->count())->toBe(0);
})->with('providers that flag unverified emails');

it('returns to sign in when the player cancels at the provider', function (string $provider) {
    Socialite::fake($provider);

    $this->get(route('social.callback', ['provider' => $provider, 'error' => 'access_denied']))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'Sign-in was cancelled.']);

    $this->assertGuest();
})->with('providers');

it('knows only Google, Discord and X', function () {
    $this->get('/auth/github/redirect')->assertNotFound();
});

it('builds the Discord driver from Socialite Providers', function () {
    config(['services.discord' => ['client_id' => 'id', 'client_secret' => 'secret', 'redirect' => '/auth/discord/callback']]);

    expect(Socialite::driver('discord'))->toBeInstanceOf(DiscordProvider::class);
});
