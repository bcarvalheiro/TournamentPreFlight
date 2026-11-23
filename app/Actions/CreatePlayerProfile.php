<?php

namespace App\Actions;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Adds a player profile to an account (M07). When the parent ticked the
 * consent box (asked only for a child), the time and the account are recorded;
 * the consenting account is always the owner. It is saved through the model,
 * so the `saving` hook sets `is_child` and still refuses a child without
 * consent (decisions.md, M04 review).
 */
class CreatePlayerProfile
{
    /**
     * @param  array{name: string, pop_id: string, birth_year: int|string, consent?: mixed}  $data
     */
    public function handle(User $user, array $data): PlayerProfile
    {
        $profile = new PlayerProfile(Arr::only($data, ['name', 'pop_id', 'birth_year']));

        if ($data['consent'] ?? false) {
            $profile->consent_given_at = now();
            $profile->consent_by_user_id = $user->id;
        }

        $user->playerProfiles()->save($profile);

        return $profile;
    }
}
