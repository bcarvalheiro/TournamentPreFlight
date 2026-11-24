<?php

namespace App\Policies;

use App\Models\PlayerProfile;
use App\Models\User;

/**
 * Only the account that holds a trainer (player profile) may change it
 * (M07 AC1). Listing needs no policy: the list query starts from the user.
 */
class PlayerProfilePolicy
{
    /**
     * Edit the trainer's name, Pokemon Player ID (POP ID) or birth year.
     */
    public function update(User $user, PlayerProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    /**
     * Remove the trainer and its answers (slice 7).
     */
    public function delete(User $user, PlayerProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }
}
