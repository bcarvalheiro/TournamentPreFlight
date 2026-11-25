<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An account's answers to one event (M08), for one trainer or several. Every
 * refusal is decided before anything is written: a trainer that is not the
 * account's own, or an unknown id (403), and an event players cannot answer
 * for, not listed or ended, the `upcoming` scope (422). Then the answers are
 * saved all or nothing: going creates the row if it is missing, so answering
 * twice never makes a second one (the unique key backs this up); not going
 * deletes it (CLAUDE.md rule 5).
 */
class SetAttendance
{
    /**
     * @param  array<int, bool>  $answers  profile id => going
     */
    public function handle(User $user, Event $event, array $answers): void
    {
        $profiles = $user->playerProfiles()->findMany(array_keys($answers));

        if ($profiles->count() !== count($answers)) {
            throw new AuthorizationException;
        }

        if (! Event::query()->upcoming()->whereKey($event->id)->exists()) {
            throw ValidationException::withMessages(['event' => 'Answers are closed for this event.']);
        }

        DB::transaction(function () use ($profiles, $event, $answers): void {
            foreach ($profiles as $profile) {
                $this->save($profile, $event, $answers[$profile->id]);
            }
        });
    }

    /**
     * Going creates the row if it is missing; not going deletes it.
     */
    private function save(PlayerProfile $profile, Event $event, bool $going): void
    {
        $answer = ['event_id' => $event->id, 'player_profile_id' => $profile->id];

        if ($going) {
            Attendance::query()->firstOrCreate($answer);
        } else {
            Attendance::query()->where($answer)->delete();
        }
    }
}
