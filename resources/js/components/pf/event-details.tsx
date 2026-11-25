import {
    CalendarPlus,
    Clock,
    Euro,
    ExternalLink,
    Hash,
    Layers,
    Share2,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useClipboard } from '@/hooks/use-clipboard';
import { formatLongDate, formatTime } from '@/lib/time';
import { TOURNAMENT_TYPE_LONG_NAMES } from '@/lib/tournament-types';
import { calendar } from '@/routes/events';
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

/** "2026-11-29T10:00:00Z" → "20261129T100000Z", the UTC form calendars read. */
function calendarTime(iso: string): string {
    return new Date(iso).toISOString().replace(/[-:]|\.\d{3}/g, '');
}

/**
 * "Add to Google Calendar": Google's template link. Google does not document
 * it; the format comes from the community "add-event-to-calendar-docs"
 * (decisions.md, 25 Nov). UTC times with Z, so Google shows the viewer's zone;
 * the same text as the `.ics` file.
 */
export function googleCalendarLink(event: EventPage): string {
    const search = new URLSearchParams({
        action: 'TEMPLATE',
        text: event.title,
        dates: `${calendarTime(event.startsAt)}/${calendarTime(event.endsAt)}`,
        location: [event.shopName, event.address, event.city]
            .filter(Boolean)
            .join(', '),
        details: `Who's going: ${event.url}\nA headcount, not a registration. Register with the shop as usual.`,
    });

    return `https://calendar.google.com/calendar/render?${search}`;
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
 * action grid (Official page when known; Share copies the `?going` link; Add to
 * calendar while the event is ahead, as a file or a Google link; Directions lives
 * only in the shop column); the
 * headcount disclaimer; where the event comes from.
 */
export function EventDetails({ event }: EventDetailsProps): ReactNode {
    const tile = 'h-auto flex-col gap-1 py-3';
    const [, copy] = useClipboard();

    /** The `?going` link opens the "Going to …?" pop-up (M09 AC6); copy only for now (phones' share sheet: M15). */
    async function shareLink(): Promise<void> {
        if (await copy(`${event.url}?going`)) {
            toast('Link copied');
        } else {
            toast.error("Couldn't copy the link.");
        }
    }

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
                <Button variant="outline" className={tile} onClick={shareLink}>
                    <Share2 aria-hidden="true" />
                    Share
                </Button>
                {/* Only while the event is ahead: nothing to plan once it ended or was cancelled. */}
                {event.canAnswer && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="outline" className={tile}>
                                <CalendarPlus aria-hidden="true" />
                                Add to calendar
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <a href={calendar.url(event.id)} download>
                                    Apple / Outlook (.ics)
                                </a>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                                <a
                                    href={googleCalendarLink(event)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    Google Calendar
                                </a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
            <p className="mt-3 rounded-xl border border-red/20 bg-red/5 px-3.5 py-3 text-sm">
                <strong>A headcount, not a registration.</strong> Tapping
                &quot;Going?&quot; tells other players you plan to attend.
                Register with the host shop as usual.
            </p>
            <p className="mt-2 font-mono text-2xs tracking-wider text-muted uppercase">
                {event.source === 'pokedata'
                    ? 'Source: pokedata'
                    : 'Added by a player'}
            </p>
        </section>
    );
}
