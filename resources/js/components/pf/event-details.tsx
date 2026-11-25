import { Clock, Euro, ExternalLink, Hash, Layers } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { formatLongDate, formatTime } from '@/lib/time';
import { TOURNAMENT_TYPE_LONG_NAMES } from '@/lib/tournament-types';
import type { EventPage } from '@/types';

type EventDetailsProps = {
    event: EventPage;
};

const euros = (fractionDigits: number) =>
    new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: fractionDigits,
    });

/**
 * "15 €", "6,50 €" or "Free": an entry fee in euro cents, written the
 * Portuguese way, as shops price it (decisions.md, 25 Nov).
 */
export function formatEntryFee(cents: number): string {
    if (cents === 0) {
        return 'Free';
    }

    return euros(cents % 100 === 0 ? 0 : 2).format(cents / 100);
}

/** One details row: icon, label, value. */
function Row({
    icon: Icon,
    label,
    children,
}: {
    icon: LucideIcon;
    label: string;
    children: ReactNode;
}): ReactNode {
    return (
        <div className="grid grid-cols-[1.25rem_7rem_1fr] items-start gap-2 border-b border-line py-3 last:border-b-0">
            <Icon aria-hidden="true" className="mt-0.5 size-4 text-muted" />
            <dt className="text-muted">{label}</dt>
            <dd className="font-semibold text-ink">{children}</dd>
        </div>
    );
}

/**
 * The event page's details column (design.md, Event page): when (the end is
 * the type's estimate, never a published time), format and tournament ID; the
 * action grid (Official page when known; Share, Slice 6, and Add to calendar,
 * Slice 10, join it later; Directions lives only in the shop column); the
 * headcount disclaimer; where the event comes from.
 */
export function EventDetails({ event }: EventDetailsProps): ReactNode {
    const tile = 'h-auto flex-col gap-1 py-3';

    return (
        <section aria-labelledby="event-details-title">
            <h2
                id="event-details-title"
                className="mb-2 font-mono text-xs font-semibold tracking-widest text-muted uppercase"
            >
                Details
            </h2>
            <dl>
                <Row icon={Clock} label="When">
                    {formatLongDate(event.startsAt)}
                    <span className="block text-sm font-normal text-muted">
                        Starts {formatTime(event.startsAt)} · ends around{' '}
                        {formatTime(event.endsAt)} (estimate)
                    </span>
                </Row>
                <Row icon={Layers} label="Format">
                    TCG · {TOURNAMENT_TYPE_LONG_NAMES[event.type]}
                </Row>
                {event.costCents !== null && (
                    <Row icon={Euro} label="Entry fee">
                        {formatEntryFee(event.costCents)}
                    </Row>
                )}
                {event.displayId && (
                    <Row icon={Hash} label="Tournament ID">
                        <span className="font-mono">{event.displayId}</span>
                    </Row>
                )}
            </dl>
            <div className="mt-3 grid grid-cols-2 gap-2">
                {event.officialUrl && (
                    <Button variant="outline" className={tile} asChild>
                        <a
                            href={event.officialUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <ExternalLink aria-hidden="true" />
                            Official page
                        </a>
                    </Button>
                )}
            </div>
            <p className="mt-3 rounded-xl border border-red/20 bg-red/5 px-3.5 py-3 text-sm">
                <strong>A headcount, not a registration.</strong> Tapping
                &quot;I&apos;m going&quot; tells other players you plan to
                attend. Register with the host shop as usual.
            </p>
            <p className="mt-2 font-mono text-2xs tracking-wider text-muted uppercase">
                {event.source === 'pokedata'
                    ? 'Source: pokedata'
                    : 'Added by a player'}
            </p>
        </section>
    );
}
