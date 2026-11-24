<?php

use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M07: an account saves its own player profile: name, Pokemon Player ID (digits,
 * unique across the app) and birth year. Saving the first one returns to the
 * page the player gate blocked, which then receives the pending intent.
 */
function validProfile(array $overrides = []): array
{
    return [
        'name' => 'Ash Trainer',
        'pop_id' => '1234567',
        'birth_year' => 1990,
        ...$overrides,
    ];
}

it('saves a profile for the signed-in account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), validProfile())
        ->assertRedirect(route('home'));

    $profile = $user->playerProfiles()->sole();
    expect($profile->name)->toBe('Ash Trainer')
        ->and($profile->pop_id)->toBe('1234567')
        ->and($profile->birth_year)->toBe(1990)
        ->and($profile->is_child)->toBeFalse();
});

it('rejects invalid input', function (array $input, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), validProfile($input))
        ->assertSessionHasErrors($field);

    expect(PlayerProfile::query()->count())->toBe(0);
})->with([
    'no name' => [['name' => ''], 'name'],
    'letters in the Pokemon Player ID (POP ID)' => [['pop_id' => 'abc'], 'pop_id'],
    'a Pokemon Player ID (POP ID) of 11 digits' => [['pop_id' => '12345678901'], 'pop_id'],
    'a decimal Pokemon Player ID (POP ID)' => [['pop_id' => '12.5'], 'pop_id'],
    'no Pokemon Player ID (POP ID)' => [['pop_id' => ''], 'pop_id'],
    'born before 1920' => [['birth_year' => 1919], 'birth_year'],
    'born next year' => [['birth_year' => now('Europe/Lisbon')->year + 1], 'birth_year'],
    'a birth year that is not a number' => [['birth_year' => 'abc'], 'birth_year'],
]);

it('refuses a Pokemon Player ID (POP ID) already on another account, with a clear message', function () {
    PlayerProfile::factory()->create(['pop_id' => '1234567']);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profiles.store'), validProfile(['pop_id' => '1234567']))
        ->assertSessionHasErrors(['pop_id' => 'This Pokemon Player ID (POP ID) is already on another profile.']);

    expect($user->playerProfiles()->exists())->toBeFalse();
});

it('sends a guest to sign in and an unverified account to verify', function () {
    $this->post(route('profiles.store'), validProfile())->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())
        ->post(route('profiles.store'), validProfile())
        ->assertRedirect(route('verification.notice'));

    expect(PlayerProfile::query()->count())->toBe(0);
});

it('returns to the page the gate blocked, which receives the intent', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('events.going', $event), ['return_to' => '/?type[]=cup']);
    $this->get('/?type[]=cup')->assertRedirect(route('profiles.create'));

    $page = url('/').'/?'.http_build_query(['type' => ['cup']]);
    $this->post(route('profiles.store'), validProfile())->assertRedirect($page);

    $this->get($page)->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', ['event_id' => $event->id]));
});

it('lets a social account without a password save its first profile and answer', function () {
    $event = Event::factory()->create();
    $user = User::factory()->create(['password' => null]);
    $this->actingAs($user);

    $this->post(route('profiles.store'), validProfile())->assertRedirect(route('home'));

    // "Using the site" means answering: the gate lets the intent through, no password asked.
    $this->post(route('events.going', $event), ['return_to' => '/'])->assertRedirect(url('/'));
    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('pendingIntent', ['event_id' => $event->id]));
});

it('shows the form with the first birth year that needs consent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profiles.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('profiles/create')
            ->where('signingInToGo', false)
            ->where('consentFromYear', now('Europe/Lisbon')->year - 13));

    $this->withSession(['pending_intent' => ['event_id' => 1]])
        ->get(route('profiles.create'))
        ->assertInertia(fn (Assert $page) => $page->where('signingInToGo', true));
});

it('returns to the profile list after adding another player', function () {
    $user = User::factory()->has(PlayerProfile::factory())->create();

    // A child: the account already holds its one profile aged 13 or older (AC7).
    $this->actingAs($user)->post(route('profiles.store'), validProfile([
        'birth_year' => now('Europe/Lisbon')->year - 9,
        'consent' => '1',
    ]))->assertRedirect(route('profiles.index'));

    expect($user->playerProfiles()->count())->toBe(2);
});

// Review fix (copy rules: short, friendly): our own words, not Laravel's defaults.
it('explains each mistake in a short sentence', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('profiles.store'), ['name' => '', 'pop_id' => '12ab', 'birth_year' => 1900])
        ->assertSessionHasErrors([
            'name' => 'Add a name.',
            'pop_id' => 'A POP ID is 1 to 10 digits.',
            'birth_year' => 'Enter a birth year from 1920 to '.now('Europe/Lisbon')->year.'.',
        ]);

    $this->post(route('profiles.store'), ['name' => 'Ana', 'pop_id' => '', 'birth_year' => ''])
        ->assertSessionHasErrors([
            'pop_id' => 'Add the Pokemon Player ID (POP ID).',
            'birth_year' => 'Add a birth year.',
        ]);
});
