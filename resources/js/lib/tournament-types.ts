/** Tournament types, from common to rare. Same values as App\Enums\EventType. */
export const TOURNAMENT_TYPES = [
    'casual',
    'prerelease',
    'challenge',
    'cup',
] as const;

export type TournamentType = (typeof TOURNAMENT_TYPES)[number];

/** The name shown to players next to the type icon. */
export const TOURNAMENT_TYPE_NAMES: Record<TournamentType, string> = {
    casual: 'Casual',
    prerelease: 'Prerelease',
    challenge: 'Challenge',
    cup: 'Cup',
};
