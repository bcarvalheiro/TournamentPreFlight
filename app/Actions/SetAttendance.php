<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * One trainer's answer to one event (M08). Going creates the row if it is
 * missing, so answering twice never makes a second one (the unique key backs
 * this up); not going deletes it (CLAUDE.md rule 5). Only the trainer's own
 * account may answer (403), and only for an event players can answer for:
 * listed and not ended, the `upcoming` scope (422).
 */
class SetAttendance
{
    public function handle(User $user, PlayerProfile $profile, Event $event, bool $going): void
    {
        if ($profile->user_id !== $user->id) {
            throw new AuthorizationException;
        }

        if (! Event::query()->upcoming()->whereKey($event->id)->exists()) {
            throw ValidationException::withMessages(['event' => 'Answers are closed for this event.']);
        }

        $answer = ['event_id' => $event->id, 'player_profile_id' => $profile->id];

        if ($going) {
            Attendance::query()->firstOrCreate($answer);
        } else {
            Attendance::query()->where($answer)->delete();
        }
    }
}
