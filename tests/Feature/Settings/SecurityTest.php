<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('security page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/security')
            ->has('passwordRules'),
        );
});

test('security page requires password confirmation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('security.edit'));

    $response->assertRedirect(route('password.confirm'));
});

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));
});

/*
 * M06: a social account needs no password but may add one. Security opens
 * without a password check (there is none to confirm) and offers a link by
 * email; the inbox proves it is the owner, then Fortify's reset page sets it.
 */

/**
 * A Google account without a password.
 */
function passwordlessAccount(): User
{
    $user = User::factory()->create(['password' => null]);
    SocialAccount::factory()->for($user)->create(['provider' => 'google']);

    return $user;
}

test('a passwordless account opens security without confirming a password', function () {
    $this->actingAs(passwordlessAccount())
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/security')
            ->where('hasPassword', false)
            ->where('signInProviders', ['Google']),
        );
});

test('a passwordless account gets a link to set a password, then is signed out', function () {
    Notification::fake();
    $user = passwordlessAccount();

    $this->actingAs($user)
        ->post(route('user-password.link'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Check your email to set your password.');

    Notification::assertSentTo($user, ResetPassword::class);
    $this->assertGuest();
});

test('an account with a password gets no link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('user-password.link'))->assertForbidden();

    Notification::assertNothingSent();
    $this->assertAuthenticatedAs($user);
});
