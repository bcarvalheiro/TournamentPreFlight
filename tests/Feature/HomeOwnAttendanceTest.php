<?php

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * M08 AC2: the home page's `own` prop tells the signed-in account which of its
 * trainers are going to each upcoming event, so the slips can say "Going ✓"
 * and "You: Ana, Tiago". Only the account's own trainers, never anyone else's;
 * guests get nothing. Counts follow the answers.
 */

it('gives a guest no own data', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('own', null));
});

it('shows an account its own trainers and their answers', function () {
    $parent = User::factory()->create();
    $adult = PlayerProfile::factory()->for($parent)->create(['name' => 'Ana']);
    $child = PlayerProfile::factory()->child()->for($parent)->create(['name' => 'Tiago']);
    [$first, $second] = Event::factory()->count(2)->create();
    $ended = Event::factory()->create(['starts_at' => now()->subHours(3)]);

    Attendance::factory()->for($adult)->for($first)->create();
    Attendance::factory()->for($child)->for($first)->create();
    Attendance::factory()->for($child)->for($second)->create();
    Attendance::factory()->for($child)->for($ended)->create();
    // Someone else going to the same event is counted, but is not "own".
    Attendance::factory()->for(PlayerProfile::factory())->for($first)->create();

    $this->actingAs($parent)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('own.trainers', [
                ['id' => $adult->id, 'name' => 'Ana'],
                ['id' => $child->id, 'name' => 'Tiago'],
            ])
            ->where('own.going', [
                $first->id => [$adult->id, $child->id],
                $second->id => [$child->id],
            ]));
});

it('shows an answer on the next home page load', function () {
    $profile = PlayerProfile::factory()->create();
    $event = Event::factory()->create();
    $this->actingAs($profile->user);

    $this->put(route('events.attendance.update', $event), ['profiles' => [$profile->id => true]]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.0.goingCount', 1)
            ->where('own.going', [$event->id => [$profile->id]]));
});
