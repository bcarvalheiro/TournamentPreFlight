import { Mail } from 'lucide-react';
import type { ReactNode } from 'react';

/**
 * A mail icon in a soft red circle with a pulsing dot: "an email is waiting
 * for you" (settings and /email/verify). Decorative only; the text or button
 * next to it says what to do. The pulse stops for people who ask for reduced
 * motion.
 */
export function MailAlertIcon(): ReactNode {
    return (
        <span
            aria-hidden
            className="relative grid size-9 shrink-0 place-items-center rounded-full bg-red/10 text-red"
        >
            <Mail className="size-4" />
            <span className="absolute top-0 right-0 flex size-2.5">
                <span className="absolute inline-flex size-full rounded-full bg-red opacity-75 motion-safe:animate-ping" />
                <span className="relative inline-flex size-full rounded-full bg-red" />
            </span>
        </span>
    );
}
