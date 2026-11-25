import type { ReactNode } from 'react';
import { GoingButton } from '@/components/pf/going-button';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import { formatDay, formatTime } from '@/lib/time';
import { TOURNAMENT_TYPE_NAMES } from '@/lib/tournament-types';
import type { EventCard } from '@/types';

/** Month, then weekday and day, then time ("Nov" / "Sat 21" / "18:30"); NOW and LIVE while it runs. */
function When({ event }: { event: EventCard }): ReactNode {
    const time = 'block text-base font-semibold';

    if (event.isOngoing) {
        return (
            <div className="text-xs leading-tight text-muted uppercase">
                <span className="block">NOW</span>
                <b className={`${time} text-red`}>LIVE</b>
            </div>
        );
    }

    // formatDay gives "Sat 21 Nov" (lib/time.ts).
    const [weekday, day, month] = formatDay(event.startsAt).split(' ');

    return (
        <div className="text-xs leading-tight text-muted uppercase tabular-nums">
            <span className="block">{month}</span>
            <span className="block">{`${weekday} ${day}`}</span>
            <b className={`${time} text-ink`}>{formatTime(event.startsAt)}</b>
        </div>
    );
}

/**
 * "Up next": the next events as a printed pairings sheet pinned to the band
 * (design.md, Band). Never follows the filters.
 */
export function PairingsSheet({ events }: { events: EventCard[] }): ReactNode {
    return (
        <section
            aria-label="Next events"
            className="relative -rotate-[0.8deg] rounded bg-paper px-3.5 pt-4 pb-2.5 font-mono text-ink shadow-sheet"
        >
            <span
                aria-hidden="true"
                className="absolute -top-1.75 left-1/2 size-4 -translate-x-1/2 rounded-full bg-pin shadow-pin"
            />
            <div className="flex items-baseline justify-between gap-2 border-b-2 border-ink px-0.5 pb-2 text-2xs font-semibold tracking-widest text-muted uppercase">
                <h2 className="font-display text-lg font-extrabold tracking-wider text-ink">
                    Up next
                </h2>
                <span>Posted {formatTime(new Date())}</span>
            </div>
            {events.length === 0 ? (
                <p className="mt-1.5 border-t border-dashed border-sheet-line px-1.5 py-3 font-sans text-sm text-muted">
                    Nothing on the calendar yet.
                </p>
            ) : (
                // One grid for the header and the rows; each uses the same four
                // columns (subgrid), so "Event" and "Trainers" sit over their column.
                <div className="grid grid-cols-[auto_1fr_auto_auto] gap-x-2.5">
                    <div
                        aria-hidden="true"
                        className="col-span-full grid grid-cols-subgrid px-1.5 pt-1.5 pb-0.5 text-2xs tracking-widest text-muted uppercase"
                    >
                        <span>When</span>
                        <span className="pl-3 sm:pl-6">Event</span>
                        <span className="text-center">Trainers</span>
                    </div>
                    <ul className="col-span-full grid grid-cols-subgrid">
                        {events.map((event) => (
                            <li
                                key={event.id}
                                className="col-span-full grid grid-cols-subgrid items-center gap-1 border-t border-dashed border-sheet-line px-1.5 py-2.25 odd:bg-ink/3"
                            >
                                <When event={event} />
                                <div className="min-w-0 pl-3 font-sans leading-tight sm:pl-6">
                                    <strong className="block truncate">
                                        {event.shopName}
                                    </strong>
                                    <span className="inline-flex items-center gap-1 font-mono text-2xs tracking-wide text-muted uppercase">
                                        <TournamentTypeIcon type={event.type} />
                                        {
                                            TOURNAMENT_TYPE_NAMES[event.type]
                                        } ·{' '}
                                        {event.city}
                                    </span>
                                </div>
                                <span className="text-center text-xl font-semibold text-red tabular-nums">
                                    {event.goingCount}
                                    <span className="sr-only">
                                        {' '}
                                        trainers going
                                    </span>
                                </span>
                                <GoingButton event={event} />
                            </li>
                        ))}
                    </ul>
                </div>
            )}
            <p className="mt-1 border-t-2 border-ink px-1 pt-2 text-2xs tracking-wider text-muted uppercase">
                Headcount, not registration · Register at the shop
            </p>
        </section>
    );
}
