<?php

namespace App\Enums;

enum EventType: string
{
    case Casual = 'casual';
    case Prerelease = 'prerelease';
    case Challenge = 'challenge';
    case Cup = 'cup';

    /**
     * The longer name in an event's details and its fallback title ("League Cup").
     * Same names as TOURNAMENT_TYPE_LONG_NAMES in resources/js/lib/tournament-types.ts.
     */
    public function longName(): string
    {
        return match ($this) {
            self::Casual => 'Casual play',
            self::Prerelease => 'Prerelease',
            self::Challenge => 'League Challenge',
            self::Cup => 'League Cup',
        };
    }

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
