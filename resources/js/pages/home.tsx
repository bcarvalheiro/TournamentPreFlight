import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { Agenda } from '@/components/pf/agenda';
import { CoinFlip } from '@/components/pf/coin-flip';
import { FilterBar } from '@/components/pf/filter-bar';
import { PairingsSheet } from '@/components/pf/pairings-sheet';
import { ProfilePicker } from '@/components/pf/profile-picker';
import PublicLayout from '@/layouts/public-layout';
import { formatDay } from '@/lib/time';
import { update } from '@/routes/events/attendance';
import type {
    EventCard,
    HomeFilters,
    OwnAttendance,
    ShopFilter,
} from '@/types';

type HomeProps = {
    events: EventCard[];
    upNext: EventCard[];
    totals: { matched: number; all: number };
    filters: HomeFilters;
    shops: ShopFilter[];
    own: OwnAttendance | null;
};

/**
 * The band's hero (design.md, Band): headline, question, what the app is and
 * the coin flip, with the pairings sheet beside it. The layout callback at the
 * bottom hands it `upNext` (decisions.md, 25 Nov: page props reach a layout
 * slot through a layout callback).
 */
function Hero({ upNext }: { upNext: EventCard[] }): ReactNode {
    return (
        // From 900px wide: text and sheet side by side, 5 parts to 7.
        <div className="grid gap-5.5 pt-3.5 pb-6.5 min-[900px]:grid-cols-[5fr_7fr] min-[900px]:items-center min-[900px]:pt-7.5 min-[900px]:pb-10">
            <div>
                {/* Grows with the screen: 2.4rem on phones up to 4.2rem. */}
                <h1 className="font-display text-[clamp(2.4rem,10vw,4.2rem)] leading-none font-extrabold text-white uppercase">
                    Pre-Flight
                </h1>
                <p className="mt-2.5 text-lg text-white">
                    Should I go or should I{' '}
                    <b className="text-red">stay at home?</b>
                </p>
                <p className="mt-2.5 max-w-[44ch] text-sm text-board-muted">
                    See how many players are heading to each Pokémon TCG
                    tournament in Portugal before you sleeve up.
                </p>
                <CoinFlip />
            </div>
            <PairingsSheet events={upNext} />
        </div>
    );
}

/**
 * The home page body: the sticky filter bar and the agenda. After a signed-out
 * "I'm going", the server hands the page the event once (`pendingIntent`, M06)
 * and the tap carries on here (M08): a solo account is set going (never
 * toggled off), a family gets the picker open for that event.
 */
export default function Home({
    events,
    upNext,
    totals,
    filters,
    shops,
    own,
}: HomeProps) {
    const { pendingIntent } = usePage().props;
    const intentEvent =
        [...events, ...upNext].find(
            (event) => event.id === pendingIntent?.event_id,
        ) ?? null;
    const isFamily = (own?.trainers.length ?? 0) > 1;

    // Once, on arrival: the server hands the intent over only once.
    useEffect(() => {
        if (!pendingIntent || own?.trainers.length !== 1) {
            return;
        }

        const trainerId = own.trainers[0].id;

        router.put(
            update.url(pendingIntent.event_id),
            { profiles: { [trainerId]: true } },
            {
                only: ['events', 'upNext', 'own'],
                preserveScroll: true,
                // Only once the answer is really saved: a save sent back (too
                // many tries) also ends here, with its own toast.
                onSuccess: (page) => {
                    const now = page.props.own as OwnAttendance | null;

                    if (
                        !(now?.going[pendingIntent.event_id] ?? []).includes(
                            trainerId,
                        )
                    ) {
                        return;
                    }

                    toast.success(
                        intentEvent
                            ? `You're going to ${intentEvent.shopName}, ${formatDay(intentEvent.startsAt)}`
                            : "You're going.",
                    );
                },
                // The event may have closed while they signed in.
                onError: (errors) =>
                    toast.error(errors.event ?? 'Could not save. Try again.'),
            },
        );
    }, []);

    return (
        <>
            <FilterBar
                filters={filters}
                shops={shops}
                listed={events.length}
                totals={totals}
            />
            <Agenda events={events} totalUpcoming={totals.all} />
            {pendingIntent && own && isFamily && intentEvent && (
                <ProfilePicker
                    event={intentEvent}
                    trainers={own.trainers}
                    goingIds={own.going[intentEvent.id] ?? []}
                    defaultOpen
                />
            )}
        </>
    );
}

// A layout callback (Inertia "Layouts › Callback Props"): it receives the page
// props, so the band's hero can show the next events.
Home.layout = (props: HomeProps) => [
    PublicLayout,
    { hero: <Hero upNext={props.upNext} /> },
];
