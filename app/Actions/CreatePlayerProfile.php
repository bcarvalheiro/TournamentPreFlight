<?php

namespace App\Actions;

use App\Models\PlayerProfile;
use App\Models\User;

/**
 * Adds a player profile to an account (M07). It is saved through the model,
 * so the `saving` hook sets `is_child` and enforces consent (decisions.md,
 * M04 review).
 */
class CreatePlayerProfile
{
    /**
     * @param  array{name: string, pop_id: string, birth_year: int|string}  $data
     */
    public function handle(User $user, array $data): PlayerProfile
    {
        return $user->playerProfiles()->create($data);
    }
}
