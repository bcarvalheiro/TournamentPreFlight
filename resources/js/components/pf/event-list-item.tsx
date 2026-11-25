import type { ReactNode } from 'react';
import { GoingButton } from '@/components/pf/going-button';
import { TournamentTypeBadge } from '@/components/pf/tournament-type-badge';
import { Card } from '@/components/ui/card';
import { formatTime } from '@/lib/time';
import { TOURNAMENT_TYPE_LONG_NAMES } from '@/lib/tournament-types';
import type { EventCard } from '@/types';

/** The punched half-circles at the top and bottom of the tear line. */
const punchedHole =
    'absolute -left-2 size-3.5 rounded-full border border-sheet-line bg-bg-2';

/**
 * One event in the agenda, styled as a tear-off slip (design.md, Event list
 * item): type and shop on the left, the headcount and "I'm going" on the stub.
 */
export function EventListItem({ event }: { event: EventCard }): ReactNode {
    return (
        // A 104px stub on the right, like the prototype.
        <Card className="grid grid-cols-[1fr_104px] gap-0 overflow-hidden border-t-4 border-t-ink py-0">
            <div className="flex min-w-0 flex-col items-start gap-1.5 px-3.5 py-3">
                <TournamentTypeBadge type={event.type} />
                <h3 className="text-lg leading-tight font-bold">
                    {event.shopName}
                </h3>
                <p className="flex flex-wrap gap-x-3.5 gap-y-1 text-sm text-muted">
                    <span>{event.city}</span>
                    {event.isOngoing ? (
                        <span className="font-semibold text-red">
                            ● Happening now
                        </span>
                    ) : (
                        <span>
                            Starts{' '}
                            <b className="font-semibold text-ink">
                                {formatTime(event.startsAt)}
                            </b>
                        </span>
                    )}
                    <span>{TOURNAMENT_TYPE_LONG_NAMES[event.type]}</span>
                </p>
                {event.possiblyCancelled && (
                    <p className="rounded border-l-4 border-amber bg-amber/20 px-2 py-1 text-sm text-ink">
                        Possibly cancelled, check with the shop
                    </p>
                )}
            </div>
            <div className="relative flex flex-col items-center justify-center gap-1.5 border-l-2 border-dashed border-sheet-line px-2 py-2.5">
                <span aria-hidden="true" className={`${punchedHole} -top-2`} />
                <span
                    aria-hidden="true"
                    className={`${punchedHole} -bottom-2`}
                />
                <span className="font-display text-4xl leading-none font-extrabold text-red tabular-nums">
                    {event.goingCount}
                    <span className="sr-only"> trainers going</span>
                </span>
                <span
                    aria-hidden="true"
                    className="text-2xs font-semibold tracking-wider text-muted uppercase"
                >
                    Trainers
                </span>
                <GoingButton
                    event={event}
                    className="h-10 w-full justify-center"
                />
            </div>
        </Card>
    );
}
