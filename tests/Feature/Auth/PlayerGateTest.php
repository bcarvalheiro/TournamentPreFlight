<?php

use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
 * M06 AC2: anyone can look at every page; only a player answers. A player has
 * a verified email and a player profile (its Pokemon Player ID (POP ID) is required); a
 * password is optional. The gate steps in only on the way to answering, when
 * the session holds a pending "Going?": then a user who is not ready yet is
 * sent to verify their email, then to add their Pokemon Player ID (POP ID). Auth pages,
 * settings and logout always stay open; guests are not affected.
 */

/**
 * The session as it is after pressing "Going?".
 */
function onTheWayToAnswer(): void
{
    test()->withSession(['pending_intent' => ['event_id' => Event::factory()->create()->id]]);
}

it('lets guests browse', function () {
    $this->get(route('home'))->assertOk();
});

it('lets a user who is not ready browse', function (Closure $user) {
    $this->actingAs($user());

    $this->get(route('home'))->assertOk();
})->with([
    'unverified' => [fn () => User::factory()->unverified()->has(PlayerProfile::factory())->create()],
    'without a Pokemon Player ID (POP ID)' => [fn () => User::factory()->create()],
]);

it('sends an unverified user on the way to answer to the verify notice', function () {
    $this->actingAs(User::factory()->unverified()->has(PlayerProfile::factory())->create());
    onTheWayToAnswer();

    $this->get(route('home'))->assertRedirect(route('verification.notice'));
});

it('sends a user without a Pokemon Player ID (POP ID) on the way to answer to add one', function () {
    $this->actingAs(User::factory()->create());
    onTheWayToAnswer();

    $this->get(route('home'))->assertRedirect(route('profiles.create'));
    $this->get(route('profiles.create'))->assertOk();
});

it('lets a social account without a password answer', function () {
    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create(['password' => null]));
    onTheWayToAnswer();

    $this->get(route('home'))->assertOk();
});

it('keeps settings and logout open on the way to answer', function () {
    $this->actingAs(User::factory()->create());
    onTheWayToAnswer();

    $this->get('/settings')->assertRedirect('/settings/profile');
    $this->get(route('profile.edit'))->assertOk();
    $this->post(route('logout'));

    $this->assertGuest();
});

it('lets a user on the way to answer reach every profile route', function () {
    // M07 adds the routes that save the first profile; a stand-in is enough here.
    Route::middleware(['web', 'auth'])->post('profiles', fn () => 'saved')->name('profiles.store');
    $this->actingAs(User::factory()->create());
    onTheWayToAnswer();

    $this->post('/profiles')->assertOk()->assertSee('saved');
});
