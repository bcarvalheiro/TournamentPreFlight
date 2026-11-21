import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { login } from '@/routes';

const goingClasses =
    'inline-flex h-8 items-center rounded-md border-[1.5px] border-red bg-red px-2.5 font-sans text-sm font-bold whitespace-nowrap text-white';

/**
 * The quick "I'm going" button (product.md). Signed out, it opens sign-in;
 * signed in, it does nothing yet: M08 makes it toggle the going answer.
 * It reads the signed-in user from the shared `auth` prop, like nav-user.tsx.
 */
export function GoingButton({ className }: { className?: string }): ReactNode {
    const { auth } = usePage().props;
    const classes = cn(goingClasses, className);

    return auth.user ? (
        <button type="button" className={classes}>
            I'm going
        </button>
    ) : (
        <Link href={login()} className={classes}>
            I'm going
        </Link>
    );
}
