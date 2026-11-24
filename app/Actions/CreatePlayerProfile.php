<?php

namespace App\Actions;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Adds a player profile to an account (M07). When the parent ticked the
 * consent box (asked only for a child), the time and the account are recorded;
 * the consenting account is always the owner. It is saved through the model,
 * so the `saving` hook sets `is_child` and still refuses a child without
 * consent (decisions.md, M04 review). After a takeover the account's own
 * "I am 13 or older" is asked again; it is recorded together with the trainer.
 */
class CreatePlayerProfile
{
    /**
     * @param  array{name: string, pop_id: string, birth_year: int|string, consent?: mixed, age_confirmed?: mixed}  $data
     */
    public function handle(User $user, array $data): PlayerProfile
    {
        $profile = new PlayerProfile(Arr::only($data, ['name', 'pop_id', 'birth_year']));

        if ($data['consent'] ?? false) {
            $profile->consent_given_at = now();
            $profile->consent_by_user_id = $user->id;
        }

        return DB::transaction(function () use ($user, $data, $profile): PlayerProfile {
            if ($data['age_confirmed'] ?? false) {
                $user->forceFill(['age_confirmed_at' => now()])->save();
            }

            $user->playerProfiles()->save($profile);

            return $profile;
        });
    }
}
