import type { TournamentType } from '@/lib/tournament-types';

/** One event card, as App\Data\Public\EventCardData sends it. Times are UTC ISO strings. */
export type EventCard = {
    id: number;
    displayId: string | null;
    type: TournamentType;
    title: string;
    shopName: string;
    city: string;
    startsAt: string;
    endsAt: string;
    isOngoing: boolean;
    goingCount: number;
    possiblyCancelled: boolean;
};
