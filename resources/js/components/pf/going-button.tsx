import { Form, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { formatDay } from '@/lib/time';
import { cn } from '@/lib/utils';
import { going } from '@/routes/events';
import { update } from '@/routes/events/attendance';
import type { EventCard, OwnAttendance } from '@/types';

const goingClasses =
    'inline-flex h-10 items-center rounded-md border-[1.5px] border-red bg-red px-2.5 font-sans text-sm font-bold whitespace-nowrap text-white disabled:opacity-60';

/** Once going: outlined, the same red border with no fill (design.md, Event list item). */
const goingOnClasses = 'bg-transparent text-red';

/**
 * The quick "I'm going" button (product.md). A player with one trainer taps it
 * to go or not go (M08): the counts and labels refresh with a partial reload
 * and a toast confirms. Everyone else posts the event (M06, a form, so a link
 * cannot set the intent): a guest signs in first, a user without a verified
 * email or a Pokemon Player ID (POP ID) is walked through them, and the page
 * comes back with the intent. Families get the picker in Slice 5.
 */
export function GoingButton({
    event,
    className,
}: {
    event: EventCard;
    className?: string;
}): ReactNode {
    const { url, props } = usePage<{ own: OwnAttendance | null }>();
    const [saving, setSaving] = useState(false);
    const trainers = props.own?.trainers ?? [];
    const solo = trainers.length === 1 ? trainers[0] : null;

    if (solo === null) {
        return (
            <Form {...going.form({ event: event.id })} className="contents">
                <input type="hidden" name="return_to" value={url} />
                <button type="submit" className={cn(goingClasses, className)}>
                    I'm going
                </button>
            </Form>
        );
    }

    const trainerId = solo.id;
    const isGoing = (props.own?.going[event.id] ?? []).includes(trainerId);
    const place = `${event.shopName}, ${formatDay(event.startsAt)}`;

    function toggle(): void {
        router.put(
            update.url(event.id),
            { profiles: { [trainerId]: !isGoing } },
            {
                only: ['events', 'upNext', 'own'],
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                // Only when the server's answer really changed: a save sent back
                // (too many tries) also ends here, with its own toast.
                onSuccess: (page) => {
                    const own = page.props.own as OwnAttendance | null;
                    const nowGoing = (own?.going[event.id] ?? []).includes(
                        trainerId,
                    );

                    if (nowGoing !== isGoing) {
                        toast.success(
                            nowGoing
                                ? `You're going to ${place}`
                                : `You're no longer going to ${place}`,
                        );
                    }
                },
                onError: (errors) =>
                    toast.error(errors.event ?? 'Could not save. Try again.'),
            },
        );
    }

    return (
        <button
            type="button"
            aria-pressed={isGoing}
            disabled={saving}
            onClick={toggle}
            className={cn(goingClasses, isGoing && goingOnClasses, className)}
        >
            {isGoing ? 'Going ✓' : "I'm going"}
        </button>
    );
}
