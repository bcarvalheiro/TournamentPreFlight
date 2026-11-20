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

/** The home page filters as the server read them (`filters` prop); dates are "YYYY-MM-DD". */
export type HomeFilters = {
    type: TournamentType[];
    shop: number[];
    when: 'any' | 'week' | 'weekend' | '7' | '30' | 'custom';
    from: string | null;
    to: string | null;
};

/** One shop of the filter sheet, as App\Data\Public\ShopFilterData sends it. */
export type ShopFilter = {
    id: number;
    name: string;
    city: string;
    eventCount: number;
};
