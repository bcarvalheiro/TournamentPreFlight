<?php

namespace App\Actions;

use App\Models\PlayerProfile;
use App\Models\User;
use Illuminate\Support\Arr;

/**
 * Changes a trainer's name, Pokemon Player ID (POP ID) or birth year (M07).
 * When the trainer becomes a child, the parent's consent (asked only then) is
 * recorded with the editing account, which is always the owner (the policy).
 * An existing child keeps the consent already given. Saved through the model,
 * so the `saving` hook updates `is_child` and still refuses a child without
 * consent (decisions.md, M04 review).
 */
class UpdatePlayerProfile
{
    /**
     * @param  array{name: string, pop_id: string, birth_year: int|string, consent?: mixed}  $data
     */
    public function handle(User $user, PlayerProfile $profile, array $data): PlayerProfile
    {
        $profile->fill(Arr::only($data, ['name', 'pop_id', 'birth_year']));

        if ($data['consent'] ?? false) {
            $profile->consent_given_at = now();
            $profile->consent_by_user_id = $user->id;
        }

        $profile->save();

        return $profile;
    }
}
