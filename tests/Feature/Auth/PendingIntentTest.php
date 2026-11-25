<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M06 AC5: a guest who presses "I'm going" signs in and comes back to the same
 * page, which receives the intent once. The intent lives in the session, never
 * in the URL, and is set only by a POST from the button (CSRF-protected), so a
 * link cannot answer for anyone. M08 acts on it.
 */
it('remembers the intent and sends the guest to sign in', function () {
    $event = Event::factory()->create();

    $this->post(route('events.going', $event), ['return_to' => '/?type[]=cup'])
        ->assertRedirect(route('login'));

    expect(session('pending_intent'))->toBe(['event_id' => $event->id])
        ->and(session('url.intended'))->toBe(url('/?type[]=cup'));
});

it('returns the player to the same page with the intent, once', function () {
    $event = Event::factory()->create();
    $user = User::factory()->has(PlayerProfile::factory())->create();

    $this->post(route('events.going', $event), ['return_to' => '/?type[]=cup']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/?type[]=cup');

    $this->get('/?type[]=cup')->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', ['event_id' => $event->id]));
    $this->get('/?type[]=cup')->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', null));
});

it('keeps the intent and the page while the gate holds the player on the profile page', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();

    $this->post(route('events.going', $event), ['return_to' => '/?type[]=cup']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->get('/?type[]=cup')->assertRedirect(route('profiles.create'));

    $this->get(route('profiles.create'))->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', null));
    expect(session('pending_intent'))->toBe(['event_id' => $event->id])
        // The gate saves the page the way Laravel rebuilds its query: type[0]=cup.
        ->and(session('url.intended'))->toBe(url('/').'/?'.http_build_query(['type' => ['cup']]));
});

it('returns to the page after the email is verified', function () {
    $event = Event::factory()->create();
    $user = User::factory()->unverified()->has(PlayerProfile::factory())->create();

    $this->post(route('events.going', $event), ['return_to' => '/?type[]=cup']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get('/?type[]=cup')->assertRedirect(route('verification.notice'));

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    $this->get($verificationUrl)->assertRedirect(url('/').'/?'.http_build_query(['type' => ['cup']]));
});

it('only returns to pages on this site', function (?string $returnTo, Closure $expected) {
    $event = Event::factory()->create();

    $this->post(route('events.going', $event), ['return_to' => $returnTo]);

    expect(session('url.intended'))->toBe($expected());
})->with([
    'another site' => ['https://evil.test/', fn () => route('home')],
    'protocol-relative' => ['//evil.test', fn () => route('home')],
    'missing' => [null, fn () => route('home')],
    // Made absolute on our host, so a browser cannot read them as "//evil.test".
    'backslash trick' => ['/\\evil.test', fn () => url('/\\evil.test')],
    'tab trick' => ["/\t/evil.test", fn () => url("/\t/evil.test")],
]);

it('sets no intent from a link', function () {
    $event = Event::factory()->create();

    $this->get(route('events.going', $event))->assertMethodNotAllowed();

    expect(session('pending_intent'))->toBeNull();
});

it('sets no intent for an event that is not coming up', function (Closure $event) {
    $this->post(route('events.going', $event()), ['return_to' => '/'])->assertNotFound();

    expect(session('pending_intent'))->toBeNull();
})->with([
    'ended' => [fn () => Event::factory()->create(['starts_at' => now()->subWeek()])],
    'hidden' => [fn () => Event::factory()->create(['status' => EventStatus::Hidden])],
    'cancelled' => [fn () => Event::factory()->create(['status' => EventStatus::Cancelled])],
    'pending' => [fn () => Event::factory()->create(['status' => EventStatus::Pending])],
]);

it('asks the database nothing for the intent when there is none', function () {
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());
    $profileQueries = 0;
    // The navbar's "Trainers (n)" count (M07) is the one profile query every
    // signed-in page makes on purpose; SharedProfilesCountTest covers it.
    DB::listen(function (QueryExecuted $query) use (&$profileQueries) {
        $profileQueries += str_contains($query->sql, 'player_profiles') && ! str_contains($query->sql, 'count(*)') ? 1 : 0;
    });

    // Settings → Profile has no reason to load trainers. Home (the `own` prop,
    // M08) and the Trainers list load them on purpose (decisions.md, 25 Nov).
    $this->get(route('profile.edit'))->assertOk();

    // Without a pending intent neither the player gate nor the prop asks.
    expect($profileQueries)->toBe(0);
});

it('tells the sign-in pages a guest is signing in to say they are going', function (string $page) {
    $this->get(route($page))->assertInertia(fn (Assert $inertia) => $inertia->where('signingInToGo', false));

    $this->post(route('events.going', Event::factory()->create()), ['return_to' => '/']);

    $this->get(route($page))->assertInertia(fn (Assert $inertia) => $inertia->where('signingInToGo', true));
})->with(['login', 'register']);

it('walks a signed-in user without a Pokemon Player ID (POP ID) from the button to the profile page and back', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->post(route('events.going', $event), ['return_to' => '/?type[]=cup'])
        ->assertRedirect(url('/?type[]=cup'));
    $this->get('/?type[]=cup')->assertRedirect(route('profiles.create'));

    expect(session('pending_intent'))->toBe(['event_id' => $event->id])
        ->and(session('url.intended'))->toBe(url('/').'/?'.http_build_query(['type' => ['cup']]));
});

it('hands a ready player\'s intent straight back to the page', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());

    $this->post(route('events.going', $event), ['return_to' => '/'])->assertRedirect(url('/'));

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', ['event_id' => $event->id]));
});

it('lets a user leave the profile page without answering', function () {
    $event = Event::factory()->create();
    $this->actingAs(User::factory()->create());
    $this->post(route('events.going', $event), ['return_to' => '/?type[]=cup']);
    $this->get('/?type[]=cup')->assertRedirect(route('profiles.create'));

    $this->get(route('profiles.create'))->assertInertia(fn (Assert $page) => $page
        ->where('signingInToGo', true));

    $this->delete(route('events.going.forget'))
        ->assertRedirect(url('/').'/?'.http_build_query(['type' => ['cup']]));

    expect(session('pending_intent'))->toBeNull();
    $this->get('/?type[]=cup')->assertOk();
    $this->get(route('profiles.create'))->assertInertia(fn (Assert $page) => $page
        ->where('signingInToGo', false));
});
