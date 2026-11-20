<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Arr;

/*
 * Rule 1 (CLAUDE.md, "Counts only"): no public page shows a player's name,
 * Player ID or birth year (architecture.md, "Privacy enforcement").
 * A player with easy-to-spot data is going to a listed event, so their data
 * sits right next to what the page shows; every public route must still hide it.
 */

/** Every public page. Each new public route is added here in the same PR. */
dataset('public pages', [
    'home' => fn () => route('home'),
]);

beforeEach(function () {
    $owner = User::factory()->create(['name' => 'Zzleak Owner', 'email' => 'zzleak@example.test']);
    $profile = PlayerProfile::factory()->for($owner)->create([
        'name' => 'Zzleak Name 4711',
        'pop_id' => '9988776655',
    ]);
    Attendance::factory()->for($profile)->for(Event::factory())->create();
});

it('shows no player name, Player ID or birth year', function (string $url, bool $signedIn) {
    if ($signedIn) {
        $this->actingAs(User::factory()->create());
    }

    $response = $this->get($url)->assertOk();

    // The leaky player's answer is counted, so the test is not passing on an empty page.
    expect($response->inertiaProps('events.0.goingCount'))->toBe(1);

    // Lower-cased first: assertDontSee matches exact case, and the owner's email is lower case.
    expect(mb_strtolower($response->getContent()))
        ->not->toContain('zzleak')
        ->not->toContain('9988776655');

    // Birth years are plain numbers that could match a date by chance, so check the keys instead.
    expect(array_keys(Arr::dot($response->inertiaProps())))
        ->each->not->toMatch('/pop_?id|birth_?year/i');
})->with('public pages')->with([
    'guest' => false,
    'another user' => true,
]);
