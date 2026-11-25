<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;

/*
 * Who may open an event page (M09 AC3). Listed and cancelled events are public;
 * pending events (waiting for moderation, M11) and hidden ones (an admin's
 * choice) answer 404 to everyone but their submitter and admins, so nobody
 * else learns they exist (EventPolicy).
 */

/** The viewer for a case: null is a guest. */
function visibilityViewer(string $viewer, Event $event): ?User
{
    return match ($viewer) {
        'guest' => null,
        'another user' => User::factory()->create(),
        'the submitter' => $event->addedBy,
        'an admin' => User::factory()->create(['is_admin' => true]),
    };
}

it('opens each event only for those allowed to see it', function (EventStatus $status, string $viewer, int $expected) {
    $event = Event::factory()->for(User::factory(), 'addedBy')->create(['status' => $status, 'title' => 'Winter Cup']);
    $user = visibilityViewer($viewer, $event);

    if ($user !== null) {
        $this->actingAs($user);
    }

    $this->get("/events/{$event->id}-winter-cup")->assertStatus($expected);
})->with([
    'listed' => [EventStatus::Listed, 'guest', 200],
    'listed, another user' => [EventStatus::Listed, 'another user', 200],
    'listed, the submitter' => [EventStatus::Listed, 'the submitter', 200],
    'listed, an admin' => [EventStatus::Listed, 'an admin', 200],
    'cancelled' => [EventStatus::Cancelled, 'guest', 200],
    'cancelled, another user' => [EventStatus::Cancelled, 'another user', 200],
    'cancelled, the submitter' => [EventStatus::Cancelled, 'the submitter', 200],
    'cancelled, an admin' => [EventStatus::Cancelled, 'an admin', 200],
    'pending' => [EventStatus::Pending, 'guest', 404],
    'pending, another user' => [EventStatus::Pending, 'another user', 404],
    'pending, the submitter' => [EventStatus::Pending, 'the submitter', 200],
    'pending, an admin' => [EventStatus::Pending, 'an admin', 200],
    'hidden' => [EventStatus::Hidden, 'guest', 404],
    'hidden, another user' => [EventStatus::Hidden, 'another user', 404],
    'hidden, the submitter' => [EventStatus::Hidden, 'the submitter', 200],
    'hidden, an admin' => [EventStatus::Hidden, 'an admin', 200],
]);

it('answers 404, not a redirect, for a pending event\'s old URL', function () {
    $event = Event::factory()->create(['status' => EventStatus::Pending, 'title' => 'Winter Cup']);

    // The canonical redirect would tell a stranger the event exists.
    $this->get("/events/{$event->id}-old-name")->assertNotFound();
});
