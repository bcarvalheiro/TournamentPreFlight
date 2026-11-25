import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { TournamentTypeBadge } from '@/components/pf/tournament-type-badge';
import { formatDay, formatRelativeDay, formatTime } from '@/lib/time';
import { home } from '@/routes';
import type { EventPage } from '@/types';

type EventHeaderProps = {
    event: EventPage;
};

/** The third tile: how soon, or the state that replaces it. */
function WhenLabel({ event }: EventHeaderProps): ReactNode {
    if (event.isCancelled) {
        return <span className="font-semibold text-white">Cancelled</span>;
    }

    if (event.hasEnded) {
        return <span className="text-board-muted">This event has ended</span>;
    }

    if (event.isOngoing) {
        return <span className="font-semibold text-red">● Happening now</span>;
    }

    return (
        <span className="font-semibold text-white">
            {formatRelativeDay(event.startsAt)}
        </span>
    );
}

/**
 * The event page's part of the dark band (design.md, Event page): back link,
 * type label, title, shop and city, then Date / Starts / when tiles, all in
 * Lisbon time. A possibly cancelled event gets the amber warning.
 */
export function EventHeader({ event }: EventHeaderProps): ReactNode {
    const tile = 'flex flex-col justify-center gap-0.5 px-3.5 py-2.5';
    const tileLabel =
        'font-mono text-2xs tracking-widest text-board-muted uppercase';
    const tileValue =
        'font-display text-3xl leading-none font-extrabold text-white uppercase tabular-nums';

    return (
        <div className="pt-2 pb-7 min-[900px]:pb-9">
            <Link
                href={home()}
                className="flex min-h-10 w-fit items-center text-sm font-semibold text-board-fg hover:text-white"
            >
                ← All events
            </Link>
            <TournamentTypeBadge
                type={event.type}
                className="mt-2 border-board-fg text-base text-board-fg"
            />
            {/* Grows with the screen: 2rem on phones up to 3.6rem. */}
            <h1 className="mt-2 font-display text-[clamp(2rem,8vw,3.6rem)] leading-none font-extrabold text-white uppercase">
                {event.title}
            </h1>
            <p className="mt-1.5 text-board-muted">
                <b className="font-semibold text-white">{event.shopName}</b> ·{' '}
                {event.city}
            </p>
            <dl className="mt-4 inline-grid grid-cols-[auto_auto_auto] divide-x divide-white/15 rounded-card border border-white/15">
                <div className={tile}>
                    <dt className={tileLabel}>Date</dt>
                    <dd className={tileValue}>{formatDay(event.startsAt)}</dd>
                </div>
                <div className={tile}>
                    <dt className={tileLabel}>Starts</dt>
                    <dd className={tileValue}>{formatTime(event.startsAt)}</dd>
                </div>
                <div className={`${tile} max-w-[12ch] text-sm leading-tight`}>
                    <WhenLabel event={event} />
                </div>
            </dl>
            {event.possiblyCancelled && (
                <p className="mt-3 max-w-md rounded border-l-4 border-amber bg-amber/20 px-2 py-1 text-sm text-white">
                    Possibly cancelled, check with the shop
                </p>
            )}
        </div>
    );
}
