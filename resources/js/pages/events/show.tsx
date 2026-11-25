import { Form, Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { EventCounts } from '@/components/pf/event-counts';
import { EventDetails } from '@/components/pf/event-details';
import { EventHeader } from '@/components/pf/event-header';
import { EventShop } from '@/components/pf/event-shop';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { TrainerToggleRows } from '@/components/pf/trainer-toggle-rows';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { formatDay } from '@/lib/time';
import { going } from '@/routes/events';
import { update } from '@/routes/events/attendance';
import type { EventPage, OwnAttendance } from '@/types';

type Props = {
    event: EventPage;
    /** The account's own trainers and answers; null unless a player and answers are open. */
    own: OwnAttendance | null;
    /** Opened from a shared `?going` link on an event people can answer (M09 AC6). */
    askGoing: boolean;
};

/** After a save, the page reloads only the count and the answers. */
const RELOAD = ['event', 'own'];

/**
 * How the viewer answers (M09 Slice 5): a player's trainers as toggle rows, or,
 * for a guest or an account that is not a player yet, one "Going?" that signs
 * in, verifies, adds a trainer and comes back here (M06). Shown in the count
 * card and in the `?going` pop-up.
 */
function AnswerControls({
    event,
    own,
}: {
    event: EventPage;
    own: OwnAttendance | null;
}) {
    if (own) {
        return (
            <TrainerToggleRows
                eventId={event.id}
                place={`${event.shopName}, ${formatDay(event.startsAt)}`}
                trainers={own.trainers}
                goingIds={own.going[event.id] ?? []}
                reload={RELOAD}
            />
        );
    }

    return (
        <Form {...going.form({ event: event.id })}>
            {/* The canonical URL, without ?going, so the way back does not ask again. */}
            <input type="hidden" name="return_to" value={event.url} />
            <Button type="submit" className="h-12 w-full font-sans text-base">
                Going?
            </Button>
        </Form>
    );
}

/**
 * The event page (M09, design.md "Event page"). The header lives in the dark
 * band; below it, three columns from 1024px: who's heading there (with the
 * answer controls), the shop and the details, stacked on phones, where a bar
 * at the bottom jumps back to the controls.
 */
export default function EventShow({ event, own, askGoing }: Props) {
    const { pendingIntent } = usePage().props;
    const place = `${event.shopName}, ${formatDay(event.startsAt)}`;
    // Open on arrival from a shared `?going` link; only a tap inside marks going.
    const [askOpen, setAskOpen] = useState(askGoing);

    // Back from signing in after "Going?" on this page (M06 pending intent):
    // a solo player is set going once; a family sees its rows and picks.
    useEffect(() => {
        if (
            pendingIntent?.event_id !== event.id ||
            own?.trainers.length !== 1
        ) {
            return;
        }

        const trainerId = own.trainers[0].id;

        router.put(
            update.url(event.id),
            { profiles: { [trainerId]: true } },
            {
                only: RELOAD,
                preserveScroll: true,
                onSuccess: (page) => {
                    const now = page.props.own as OwnAttendance | null;

                    if ((now?.going[event.id] ?? []).includes(trainerId)) {
                        toast.success(`You're going to ${place}`);
                    }
                },
                onError: (errors) =>
                    toast.error(errors.event ?? 'Could not save. Try again.'),
            },
        );
    }, []);

    return (
        <>
            <Head title={event.title} />
            <div className="grid gap-6 lg:grid-cols-3">
                <EventCounts
                    goingCount={event.goingCount}
                    confirmedCount={event.confirmedCount}
                >
                    {event.canAnswer && (
                        <AnswerControls event={event} own={own} />
                    )}
                </EventCounts>
                <EventShop shop={event} />
                <EventDetails event={event} />
            </div>
            {askGoing && (
                <ResponsiveDialog
                    open={askOpen}
                    onOpenChange={setAskOpen}
                    title={`Going to ${event.shopName}?`}
                    description={formatDay(event.startsAt)}
                >
                    <div className="flex flex-col gap-3">
                        <p className="text-muted">
                            {event.goingCount}{' '}
                            {event.goingCount === 1 ? 'trainer' : 'trainers'}{' '}
                            going · a headcount, not a registration
                        </p>
                        <AnswerControls event={event} own={own} />
                    </div>
                </ResponsiveDialog>
            )}
            {event.canAnswer && (
                // Phones only: sticks to the bottom while the page scrolls, then rests
                // above the footer; jumps back to the controls in the count card.
                <div
                    data-test="going-bar"
                    className="sticky bottom-0 -mx-4 mt-6 flex items-center justify-between gap-3 border-t border-line bg-bg px-4 py-3 md:hidden"
                >
                    <p className="flex items-baseline gap-1.5">
                        <span className="font-display text-3xl leading-none font-extrabold text-red tabular-nums">
                            {event.goingCount}
                        </span>
                        <span className="text-sm text-muted">going</span>
                    </p>
                    <Button asChild className="h-11">
                        <a href="#going">
                            {own ? "Choose who's going" : 'Going?'}
                        </a>
                    </Button>
                </div>
            )}
        </>
    );
}

// A layout callback (Inertia "Layouts › Callback Props"): it receives the page
// props, so the band's hero can show this event.
EventShow.layout = (props: Props) => [
    PublicLayout,
    { hero: <EventHeader event={props.event} /> },
];
