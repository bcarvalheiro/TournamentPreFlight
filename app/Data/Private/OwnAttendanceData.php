<?php

namespace App\Data\Private;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The signed-in account's own "I'm going" answers (M08), for the home page:
 * its trainers (id and name only) and which of them are going to each
 * upcoming event. Sent only to that account, never to other players
 * (CLAUDE.md rule 1). Covers every upcoming event, not only those on screen,
 * so the picker knows the answers for any event it opens.
 */
final readonly class OwnAttendanceData
{
    /**
     * @param  list<array{id: int, name: string}>  $trainers  oldest first
     * @param  array<int, list<int>>  $going  event id => ids of the account's trainers going
     */
    public function __construct(
        public array $trainers,
        public array $going,
    ) {}

    public static function forUser(User $user): self
    {
        $profiles = $user->playerProfiles()
            ->oldest('id')
            ->with(['attendances' => fn (HasMany $query) => $query->whereHas('event', fn (Builder $query) => $query->upcoming())])
            ->get(['id', 'user_id', 'name']);

        $trainers = [];
        $going = [];

        foreach ($profiles as $profile) {
            $trainers[] = ['id' => $profile->id, 'name' => $profile->name];

            foreach ($profile->attendances as $attendance) {
                $going[$attendance->event_id][] = $profile->id;
            }
        }

        return new self(trainers: $trainers, going: $going);
    }
}
