<?php

use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M06 AC5: a guest who presses "I'm going" signs in and comes back to the same
 * page, which receives the intent once. The intent lives in the session, never
 * in the URL, so a link cannot answer for anyone. M08 acts on it.
 */
it('remembers the intent and sends the guest to sign in', function () {
    $event = Event::factory()->create();

    $this->get(route('events.going', ['event' => $event, 'return_to' => '/?type[]=cup']))
        ->assertRedirect(route('login'));

    expect(session('pending_intent'))->toBe(['event_id' => $event->id])
        ->and(session('url.intended'))->toBe(url('/?type[]=cup'));
});

it('returns the player to the same page with the intent, once', function () {
    $event = Event::factory()->create();
    $user = User::factory()->has(PlayerProfile::factory())->create();

    $this->get(route('events.going', ['event' => $event, 'return_to' => '/?type[]=cup']));
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/?type[]=cup');

    $this->get('/?type[]=cup')->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', ['event_id' => $event->id]));
    $this->get('/?type[]=cup')->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', null));
});

it('keeps the intent while the gate holds the player on the profile page', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $this->get(route('events.going', ['event' => $event, 'return_to' => '/']));
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->get(route('profiles.create'))->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', null));
    expect(session('pending_intent'))->toBe(['event_id' => $event->id]);
});

it('only returns to pages on this site', function (?string $returnTo, Closure $expected) {
    $event = Event::factory()->create();

    $this->get(route('events.going', ['event' => $event, 'return_to' => $returnTo]));

    expect(session('url.intended'))->toBe($expected());
})->with([
    'another site' => ['https://evil.test/', fn () => route('home')],
    'protocol-relative' => ['//evil.test', fn () => route('home')],
    'missing' => [null, fn () => route('home')],
    // Made absolute on our host, so a browser cannot read them as "//evil.test".
    'backslash trick' => ['/\\evil.test', fn () => url('/\\evil.test')],
    'tab trick' => ["/\t/evil.test", fn () => url("/\t/evil.test")],
]);
