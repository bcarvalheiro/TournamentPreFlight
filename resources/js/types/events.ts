import type { TournamentType } from '@/lib/tournament-types';

/** One event card, as App\Data\Public\EventCardData sends it. Times are UTC ISO strings. */
export type EventCard = {
    id: number;
    /** The event page, slug included. */
    url: string;
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

/** One event on its page, as App\Data\Public\EventPageData sends it. Times are UTC ISO strings. */
export type EventPage = {
    id: number;
    /** The canonical page URL, slug included. */
    url: string;
    /** Imported from pokedata, or added by a player (M11). */
    source: 'pokedata' | 'player';
    displayId: string | null;
    type: TournamentType;
    title: string;
    shopName: string;
    address: string | null;
    district: string | null;
    city: string;
    latitude: number | null;
    longitude: number | null;
    startsAt: string;
    endsAt: string;
    officialUrl: string | null;
    /** Entry fee in euro cents; null unknown, 0 free. */
    costCents: number | null;
    goingCount: number;
    confirmedCount: number;
    isOngoing: boolean;
    hasEnded: boolean;
    isCancelled: boolean;
    possiblyCancelled: boolean;
    /** Listed and not ended: the going controls and the pop-up show only then. */
    canAnswer: boolean;
};
