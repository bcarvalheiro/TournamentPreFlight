<?php

namespace App\Enums;

enum EventType: string
{
    case Casual = 'casual';
    case Prerelease = 'prerelease';
    case Challenge = 'challenge';
    case Cup = 'cup';

    /**
     * How long an event of this type is assumed to last; the source has no end time.
     */
    public function estimatedHours(): int
    {
        return match ($this) {
            self::Casual => 2,
            self::Prerelease => 3,
            self::Challenge => 4,
            self::Cup => 6,
        };
    }
}
