import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { EventListItem } from '@/components/pf/event-list-item';
import { Button } from '@/components/ui/button';
import { Empty, EmptyContent, EmptyDescription } from '@/components/ui/empty';
import { formatDay, formatRelativeDay, groupByLisbonDay } from '@/lib/time';
import { home } from '@/routes';
import type { EventCard } from '@/types';

type AgendaProps = {
    /** The listed events (filtered, at most 25), sorted by start. */
    events: EventCard[];
    /** Every upcoming event, ignoring the filters (`totals.all`). */
    totalUpcoming: number;
};

/**
 * The event list as a day-by-day agenda (design.md, Agenda). From 700px wide
 * the day header sits in a 120px left column and stays visible while you scroll.
 */
export function Agenda({ events, totalUpcoming }: AgendaProps): ReactNode {
    if (events.length === 0) {
        return totalUpcoming === 0 ? (
            <Empty>
                <EmptyDescription>
                    Nothing on the calendar yet.
                </EmptyDescription>
            </Empty>
        ) : (
            <Empty>
                <EmptyDescription>
                    Nothing matches these filters.
                </EmptyDescription>
                <EmptyContent>
                    <Button asChild>
                        <Link href={home()}>Clear filters</Link>
                    </Button>
                </EmptyContent>
            </Empty>
        );
    }

    return (
        <div className="mx-auto max-w-[760px] space-y-6">
            {groupByLisbonDay(events).map((group) => (
                <section
                    key={group.day}
                    aria-label={formatDay(group.day)}
                    className="space-y-3 min-[700px]:grid min-[700px]:grid-cols-[120px_1fr] min-[700px]:gap-4 min-[700px]:space-y-0"
                >
                    <h2 className="flex items-baseline gap-2 self-start border-l-4 border-red pl-2 min-[700px]:sticky min-[700px]:top-4 min-[700px]:flex-col min-[700px]:gap-0">
                        <span className="font-display text-xl font-extrabold tracking-wide uppercase">
                            {formatDay(group.day)}
                        </span>
                        <span className="text-sm text-muted">
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
            ))}
        </div>
    );
}
