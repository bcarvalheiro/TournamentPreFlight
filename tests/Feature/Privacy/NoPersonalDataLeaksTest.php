<?php

use App\Http\Middleware\HandleInertiaRequests;
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

/**
 * Every public page, with where its props keep the family's event going count.
 * Each new public route is added here in the same PR.
 */
dataset('public pages', [
    'home' => [fn () => route('home'), 'events.0.goingCount'],
    'event page' => [fn () => route('events.show', ['event' => Event::sole(), 'slug' => Event::sole()->slug()]), 'event.goingCount'],
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

it('shows no player name, Pokemon Player ID (POP ID) or birth year', function (string $url, string $countProp, bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());
    }

    $response = $this->get($url)->assertOk();

    // Both answers are counted, so the test is not passing on an empty page.
    expect($response->inertiaProps($countProp))->toBe(2);

    expectNoPlayerData($response);
})->with('public pages')->with([
    'guest' => false,
    'another user' => true,
]);

it('sends the home page\'s `own` with only the viewer\'s own trainer', function (bool $signedIn) {
    $viewer = $signedIn ? User::factory()->has(PlayerProfile::factory())->create() : null;

    if ($viewer) {
        $this->actingAs($viewer);
    }

    $response = $this->get(route('home'))->assertOk();

    // `own` (M08) holds only the viewer's own trainer, never the family's ids (review fix).
    expect($response->inertiaProps('own'))->toBe($viewer ? [
        'trainers' => [['id' => $viewer->playerProfiles()->sole()->id, 'name' => $viewer->playerProfiles()->sole()->name]],
        'going' => [],
    ] : null);
})->with([
    'guest' => false,
    'another user' => true,
]);

/**
 * The partial reloads public pages make (a JSON answer instead of the HTML
 * page): the page component, its URL, the props asked for, where the family's
 * count sits, and a prop the answer must leave out.
 */
dataset('partial reloads', [
    // The going button (M08): `only: events, upNext, own`.
    'home' => ['home', fn () => route('home'), 'events,upNext,own', 'events.0.goingCount', 'shops'],
    // The event page's 60s poll (M09 Slice 9): `only: event`.
    'event page' => ['events/show', fn () => route('events.show', ['event' => Event::sole(), 'slug' => Event::sole()->slug()]), 'event', 'event.goingCount', 'own'],
]);

/*
 * A partial reload must hide the family's data just as the full page does
 * (review fix, M08), and send only what was asked for.
 */
it('shows no player data on a partial reload', function (string $component, string $url, string $only, string $countProp, string $leftOut, bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());
    }

    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => $component,
        'X-Inertia-Partial-Data' => $only,
    ])->get($url)->assertOk();

    // A partial answer: the requested props, not the whole page.
    expect($response->json('props'))->toHaveKeys(explode(',', $only))->not->toHaveKey($leftOut);
    expect($response->json("props.{$countProp}"))->toBe(2);

    // The same scans as expectNoPlayerData(), on the JSON props (that helper reads an HTML page).
    $props = $response->json('props');
    expect(mb_strtolower((string) $response->getContent()))
        ->not->toContain('zzleak')
        ->not->toContain('9988776655')
        ->not->toContain('9988776656');
    expect(json_encode($props))->not->toContain('1913');
    expect(array_keys(Arr::dot($props)))->each->not->toMatch('/pop_?id|birth_?year/i');
})->with('partial reloads')->with([
    'guest' => false,
    'another user' => true,
]);

/*
 * "Add to calendar" (M09 Slice 10) is a file, not a page: the same scans on
 * its text. It goes into people's calendars and gets forwarded.
 */
it('puts no player data in the calendar file', function (bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());
    }

    $content = mb_strtolower((string) $this->get(route('events.calendar', Event::sole()))->assertOk()->getContent());

    expect($content)
        ->toContain('begin:vevent')
        ->not->toContain('zzleak')
        ->not->toContain('9988776655')
        ->not->toContain('9988776656')
        // As a number of its own: the timestamps (…T191300Z at 19:13) contain those digits.
        ->not->toMatch('/(?<!\d)1913(?!\d)/');
})->with([
    'guest' => false,
    'another user' => true,
]);

/*
 * M08 AC6: the home page carries one owner-only prop, `own`, so the family's
 * slips can say "You: Zzleak Name 4711, Zzleak Child 4712". The family sees its
 * names there, still with no Pokemon Player ID (POP ID) or birth year; the
 * guest and other-user cases above prove nobody else does.
 */
it('shows the family their own trainers\' names on the home page', function () {
    $this->actingAs(User::query()->where('email', 'zzleak@example.test')->sole());

    $response = $this->get(route('home'))->assertOk();

    expect(collect($response->inertiaProps('own.trainers'))->pluck('name')->all())
        ->toBe(['Zzleak Name 4711', 'Zzleak Child 4712']);

    $content = (string) $response->getContent();
    expect($content)->not->toContain('9988776655')->not->toContain('9988776656');
    expect(json_encode($response->inertiaProps()))->not->toContain('1913');
    expect(array_keys(Arr::dot($response->inertiaProps())))
        ->each->not->toMatch('/pop_?id|birth_?year/i');
});

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

// Review fix: another account cannot open a family trainer's edit page, nor see its data.
it('refuses another account the edit page of the family\'s trainers', function () {
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create());

    PlayerProfile::query()->where('name', 'like', 'Zzleak%')->get()->each(function (PlayerProfile $profile) {
        $response = $this->get(route('profiles.edit', $profile))->assertForbidden();

        expect(mb_strtolower((string) $response->getContent()))
            ->not->toContain('zzleak')
            ->not->toContain('9988776655')
            ->not->toContain('9988776656');
    });
});
