import { Form, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { going } from '@/routes/events';

const goingClasses =
    'inline-flex h-10 items-center rounded-md border-[1.5px] border-red bg-red px-2.5 font-sans text-sm font-bold whitespace-nowrap text-white';

/**
 * The quick "I'm going" button (product.md). It posts the event (a form, so a
 * link cannot set the intent): a guest signs in first, a user without a
 * verified email or a Player ID is walked through them, and the page comes
 * back with the intent. M08 makes it toggle the going answer for players.
 */
export function GoingButton({
    eventId,
    className,
}: {
    eventId: number;
    className?: string;
}): ReactNode {
    const { url } = usePage();

    return (
        <Form {...going.form({ event: eventId })} className="contents">
            <input type="hidden" name="return_to" value={url} />
            <button type="submit" className={cn(goingClasses, className)}>
                I'm going
            </button>
        </Form>
    );
}
