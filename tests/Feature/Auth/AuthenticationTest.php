<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('signed-in players have no dashboard or appearance page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/dashboard')->assertNotFound();
    $this->get('/settings/appearance')->assertNotFound();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

// M06 AC6: signing out ends the session: a new session id, and nothing a
// guest or the player left in it (a pending "Going?", a social sign-up).
test('users can logout', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession([
        'pending_intent' => ['event_id' => 1],
        'social_signup' => ['provider' => 'google', 'provider_user_id' => 'id-789'],
    ]);
    $sessionId = session()->getId();

    $response = $this->post(route('logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->has('pending_intent'))->toBeFalse()
        ->and(session()->has('social_signup'))->toBeFalse();
});

// M06 AC6: five wrong passwords for one email from one address, then even the
// right password is refused for a minute (the `login` limiter).
test('users are rate limited', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');
    }

    // Too many tries: back to the page with the wait message (M08 Slice 4b).
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect()
        ->assertInertiaFlash('toast.type', 'error');
    $this->assertGuest();
});
