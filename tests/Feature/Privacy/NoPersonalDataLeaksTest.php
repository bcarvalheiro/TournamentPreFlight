<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Rule 1 (CLAUDE.md, "Counts only"): no public page shows a player's name,
 * Pokemon Player ID (POP ID) or birth year (architecture.md, "Privacy enforcement").
 * A family with easy-to-spot data (an adult and an under-13 child, rule 2's
 * profiles) is going to a listed event, so their data sits right next to what
 * the page shows; every public route must still hide it.
 */

/** Every public page. Each new public route is added here in the same PR. */
dataset('public pages', [
    'home' => fn () => route('home'),
]);

/**
 * The sign-in pages (M06), each with the viewer who can open it. They show no
 * events, but share the same Inertia props, so they are checked too.
 */
dataset('sign-in pages', [
    'login' => ['login', 'auth/login', 'guest'],
    'register' => ['register', 'auth/register', 'guest'],
    'forgot password' => ['password.request', 'auth/forgot-password', 'guest'],
    'welcome' => ['welcome', 'auth/welcome', 'social sign-up'],
    'verify email' => ['verification.notice', 'auth/verify-email', 'unverified user'],
    'first profile' => ['profiles.create', 'profiles/create', 'user without a profile'],
]);

/**
 * The family's name, Pokemon Player IDs (POP IDs) and birth year appear nowhere in the page.
 */
function expectNoPlayerData(TestResponse $response): void
{
    // Lower-cased first: assertDontSee matches exact case, and the owner's email is lower case.
    expect(mb_strtolower((string) $response->getContent()))
        ->not->toContain('zzleak')
        ->not->toContain('9988776655')
        ->not->toContain('9988776656');

    // The props only: asset file names in the HTML could contain "1913" by chance.
    expect(json_encode($response->inertiaProps()))->not->toContain('1913');

    // Birth years are plain numbers that could match a date by chance, so check the keys instead.
    expect(array_keys(Arr::dot($response->inertiaProps())))
        ->each->not->toMatch('/pop_?id|birth_?year/i');
}

beforeEach(function () {
    $owner = User::factory()->create(['name' => 'Zzleak Owner', 'email' => 'zzleak@example.test']);
    $adult = PlayerProfile::factory()->for($owner)->create([
        'name' => 'Zzleak Name 4711',
        'pop_id' => '9988776655',
        'birth_year' => 1913, // a year no event date or time can contain
    ]);
    $child = PlayerProfile::factory()->child()->for($owner)->create([
        'name' => 'Zzleak Child 4712',
        'pop_id' => '9988776656',
    ]);
    $event = Event::factory()->create();
    Attendance::factory()->for($adult)->for($event)->create();
    Attendance::factory()->for($child)->for($event)->create();
});

it('shows no player name, Pokemon Player ID (POP ID) or birth year', function (string $url, bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());
    }

    $response = $this->get($url)->assertOk();

    // Both answers are counted, so the test is not passing on an empty page.
    expect($response->inertiaProps('events.0.goingCount'))->toBe(2);

    expectNoPlayerData($response);
})->with('public pages')->with([
    'guest' => false,
    'another user' => true,
]);

it('shows no player data on the sign-in pages', function (string $route, string $component, string $viewer) {
    match ($viewer) {
        'guest' => null,
        'social sign-up' => $this->withSession(['social_signup' => [
            'provider' => 'google', 'provider_user_id' => 'id-789', 'name' => 'Ana Lima', 'email' => 'ana@example.test',
            'expires_at' => now()->addMinutes(10)->getTimestamp(),
        ]]),
        'unverified user' => $this->actingAs(User::factory()->unverified()->create()),
        'user without a profile' => $this->actingAs(User::factory()->create()),
    };

    // The right page rendered, so the test is not passing on a redirect.
    $response = $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));

    expectNoPlayerData($response);
})->with('sign-in pages');

/*
 * The profile list (M07) is an own-data page: the viewer's own profiles carry
 * Pokemon Player IDs and birth years there by design, so only the family's
 * values are checked, not the keys.
 */
it('shows another account none of the family on its profile list', function () {
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());

    $response = $this->get(route('profiles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('profiles/index')->has('profiles', 1));

    expect(mb_strtolower((string) $response->getContent()))
        ->not->toContain('zzleak')
        ->not->toContain('9988776655')
        ->not->toContain('9988776656');
    expect(json_encode($response->inertiaProps()))->not->toContain('1913');
});
