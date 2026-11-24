<?php

namespace App\Data\Private;

use App\Models\PlayerProfile;

/**
 * One player profile as its owner sees it on their profile list (M07). Sent
 * only to the account that owns the profile, never to other players
 * (CLAUDE.md rule 1).
 */
final readonly class OwnProfileData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $popId,
        public int $birthYear,
        public bool $isChild,
    ) {}

    public static function fromModel(PlayerProfile $profile): self
    {
        return new self(
            id: $profile->id,
            name: $profile->name,
            popId: $profile->pop_id,
            birthYear: $profile->birth_year,
            isChild: $profile->is_child,
        );
    }
}
