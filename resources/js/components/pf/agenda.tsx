import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { EventListItem } from '@/components/pf/event-list-item';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription } from '@/components/ui/empty';
import { formatDay, formatRelativeDay, groupByLisbonDay } from '@/lib/time';
import {
    TOURNAMENT_TYPE_NAMES,
    TOURNAMENT_TYPES,
} from '@/lib/tournament-types';
import { home } from '@/routes';
import type { EventCard } from '@/types';

type AgendaProps = {
    /** The listed events (filtered, at most 25), sorted by start. */
    events: EventCard[];
    /** Every upcoming event, ignoring the filters (`totals.all`). */
    totalUpcoming: number;
};

/** The four type icons in rarity order (design.md, Type icons). */
function TypeLegend(): ReactNode {
    return (
        <p className="flex flex-wrap items-center gap-x-3.5 gap-y-1.5 rounded-lg border border-line bg-bg px-3 py-2.5 text-sm font-semibold">
            <span className="font-normal text-muted">Common</span>
            {TOURNAMENT_TYPES.map((type) => (
                <span key={type} className="inline-flex items-center gap-1">
                    <TournamentTypeIcon type={type} size={20} />
                    {TOURNAMENT_TYPE_NAMES[type]}
                </span>
            ))}
            <span className="font-normal text-muted">Rare</span>
        </p>
    );
}

/** What to say when no event is listed: filters hid them all, or there are none. */
function NothingListed({
    totalUpcoming,
}: {
    totalUpcoming: number;
}): ReactNode {
    return totalUpcoming === 0 ? (
        <Empty>
            <EmptyDescription>Nothing on the calendar yet.</EmptyDescription>
        </Empty>
    ) : (
        <Empty>
            <EmptyDescription>Nothing matches these filters.</EmptyDescription>
            <EmptyContent>
                <Button asChild>
                    <Link href={home()}>Clear filters</Link>
                </Button>
            </EmptyContent>
        </Empty>
    );
}

/**
 * The event list as a day-by-day agenda (design.md, Agenda), between the type
 * legend and the headcount disclaimer. From 700px wide the day header sits in
 * a 120px left column and stays visible while you scroll.
 */
export function Agenda({ events, totalUpcoming }: AgendaProps): ReactNode {
    return (
        <div className="mx-auto max-w-[760px] space-y-6">
            <TypeLegend />
            {events.length === 0 ? (
                <NothingListed totalUpcoming={totalUpcoming} />
            ) : (
                groupByLisbonDay(events).map((group) => (
                    <section
                        key={group.day}
                        aria-label={formatDay(group.day)}
                        className="space-y-3 min-[700px]:grid min-[700px]:grid-cols-[120px_1fr] min-[700px]:gap-4 min-[700px]:space-y-0"
                    >
                        {/* Like the prototype: "FRI 20 NOV · TODAY" on phones; from 700px the day is big and wraps in its column, the label under it. */}
                        <h2 className="self-start border-l-5 border-red pl-2.5 font-display leading-none font-bold tracking-wide uppercase min-[700px]:sticky min-[700px]:top-20">
                            <span className="text-2xl leading-none min-[700px]:block min-[700px]:text-[2rem] min-[700px]:leading-[0.95]">
                                {formatDay(group.day)}
                            </span>{' '}
                            {/* The space keeps "Sat 21 Nov Tomorrow" two words for screen readers. */}
                            <span className="ml-1.5 text-base text-muted min-[700px]:ml-0">
                                {formatRelativeDay(group.day)}
                            </span>
                        </h2>
                        <ul className="space-y-3">
                            {group.events.map((event) => (
                                <li key={event.id}>
                                    <EventListItem event={event} />
                                </li>
                            ))}
                        </ul>
                    </section>
                ))
            )}
            <p className="rounded-xl border border-red/20 bg-red/5 px-3.5 py-3 text-sm">
                <strong>A headcount, not a registration.</strong> Tapping
                &quot;I&apos;m going&quot; tells other players you plan to
                attend. Register with the host shop as usual.
            </p>
        </div>
    );
}
