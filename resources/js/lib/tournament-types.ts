/** Play! Pokémon tournament types, from common to rare. */
export const TOURNAMENT_TYPES = [
    'friendly',
    'league',
    'challenge',
    'cup',
] as const;

export type TournamentType = (typeof TOURNAMENT_TYPES)[number];

/** The name shown to players next to the type icon. */
export const TOURNAMENT_TYPE_NAMES: Record<TournamentType, string> = {
    friendly: 'Friendly',
    league: 'League',
    challenge: 'Challenge',
    cup: 'Cup',
};
