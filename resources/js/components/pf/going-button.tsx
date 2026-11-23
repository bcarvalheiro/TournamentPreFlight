import { Form, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { going } from '@/routes/events';

const goingClasses =
    'inline-flex h-8 items-center rounded-md border-[1.5px] border-red bg-red px-2.5 font-sans text-sm font-bold whitespace-nowrap text-white';

/**
 * The quick "I'm going" button (product.md). Signed out, it posts the event
 * (a form, so a link cannot set the intent), which opens sign-in and comes
 * back to this page afterwards; signed in, it does nothing yet: M08 makes it
 * toggle the going answer.
 * It reads the signed-in user from the shared `auth` prop, like nav-user.tsx.
 */
export function GoingButton({
    eventId,
    className,
}: {
    eventId: number;
    className?: string;
}): ReactNode {
    const { auth } = usePage().props;
    const { url } = usePage();
    const classes = cn(goingClasses, className);

    return auth.user ? (
        <button type="button" className={classes}>
            I'm going
        </button>
    ) : (
        <Form {...going.form({ event: eventId })} className="contents">
            <input type="hidden" name="return_to" value={url} />
            <button type="submit" className={classes}>
                I'm going
            </button>
        </Form>
    );
}
