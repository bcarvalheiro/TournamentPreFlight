<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Who may open an event page (M09 AC3).
 */
class EventPolicy
{
    /**
     * Listed and cancelled events are public. Pending (waiting for moderation)
     * and hidden ones open only for their submitter and admins; anyone else
     * gets 404, so they never learn the event exists. Guests reach this method
     * through the optional user. Reads `is_admin` until M11 adds the admin Gate.
     */
    public function view(?User $user, Event $event): Response
    {
        $isPublic = in_array($event->status, [EventStatus::Listed, EventStatus::Cancelled], true);
        $isTheirs = $user !== null && ($user->is_admin || $event->added_by_user_id === $user->id);

        return $isPublic || $isTheirs ? Response::allow() : Response::denyAsNotFound();
    }
}
