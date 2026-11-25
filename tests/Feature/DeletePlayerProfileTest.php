<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M07 AC1 and AC6: the owner deletes a trainer, and the trainer's "Going?"
 * answers go with it (the attendances foreign key cascades). Anyone else gets
 * 403. The last trainer may go too.
 */
it('deletes the trainer and their answers, keeping other trainers\' answers', function () {
    $owner = User::factory()->create();
    $adult = PlayerProfile::factory()->for($owner)->create();
    $child = PlayerProfile::factory()->child()->for($owner)->create();
    $event = Event::factory()->create();
    Attendance::factory()->for($adult)->for($event)->create();
    Attendance::factory()->for($child)->for($event)->create();
    Attendance::factory()->for($child)->create();

    $this->actingAs($owner)->delete(route('profiles.destroy', $child))
        ->assertRedirect(route('profiles.index'));

    expect(PlayerProfile::query()->find($child->id))->toBeNull()
        ->and(Attendance::query()->where('player_profile_id', $child->id)->count())->toBe(0)
        ->and(Attendance::query()->where('player_profile_id', $adult->id)->count())->toBe(1);
});

it('refuses another account', function () {
    $profile = PlayerProfile::factory()->create();
    Attendance::factory()->for($profile)->create();

    $this->actingAs(User::factory()->has(PlayerProfile::factory())->create())
        ->delete(route('profiles.destroy', $profile))
        ->assertForbidden();

    expect($profile->fresh())->not->toBeNull()
        ->and($profile->attendances()->count())->toBe(1);
});

it('lets the last trainer go, and the account is back to none', function () {
    $profile = PlayerProfile::factory()->create();

    $this->actingAs($profile->user)->delete(route('profiles.destroy', $profile))
        ->assertRedirect(route('profiles.index'));

    $this->get(route('profiles.index'))->assertInertia(fn (Assert $page) => $page
        ->has('profiles', 0)
        ->where('auth.profilesCount', 0));
});
