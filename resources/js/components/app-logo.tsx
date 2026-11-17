import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';

/**
 * The brand: card mark + app name ("PRE-FLIGHT") in the display font.
 * The name comes from config('app.name'), shared by HandleInertiaRequests.
 * Text colour is inherited, so it works on the dark band and on light pages.
 */
export default function AppLogo({ className }: { className?: string }) {
    const { name } = usePage().props;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 font-display text-xl font-extrabold tracking-[0.06em] uppercase',
                className,
            )}
        >
            <AppLogoIcon className="h-[22px] w-6 shrink-0" />
            {name}
        </span>
    );
}
