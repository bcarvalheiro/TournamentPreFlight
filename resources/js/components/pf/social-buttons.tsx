import type { ReactNode, SVGProps } from 'react';
import { Button } from '@/components/ui/button';
import { redirect } from '@/routes/social';

export type SocialProviderName = 'google' | 'discord' | 'x';

/** Google's "G", in its own four colours (Google sign-in branding guidelines). */
function GoogleMark(props: SVGProps<SVGSVGElement>): ReactNode {
    return (
        <svg viewBox="10 10 20 20" {...props}>
            <path
                fill="#4285F4"
                d="M29.6 20.2273C29.6 19.5182 29.5364 18.8364 29.4182 18.1818H20V22.05H25.3818C25.15 23.3 24.4455 24.3591 23.3864 25.0682V27.5773H26.6182C28.5091 25.8364 29.6 23.2727 29.6 20.2273Z"
            />
            <path
                fill="#34A853"
                d="M20 30C22.7 30 24.9636 29.1045 26.6181 27.5773L23.3863 25.0682C22.4909 25.6682 21.3454 26.0227 20 26.0227C17.3954 26.0227 15.1909 24.2636 14.4045 21.9H11.0636V24.4909C12.7091 27.7591 16.0909 30 20 30Z"
            />
            <path
                fill="#FBBC04"
                d="M14.4045 21.9C14.2045 21.3 14.0909 20.6591 14.0909 20C14.0909 19.3409 14.2045 18.7 14.4045 18.1V15.5091H11.0636C10.3864 16.8591 10 18.3864 10 20C10 21.6136 10.3864 23.1409 11.0636 24.4909L14.4045 21.9Z"
            />
            <path
                fill="#E94235"
                d="M20 13.9773C21.4681 13.9773 22.7863 14.4818 23.8227 15.4727L26.6909 12.6045C24.9591 10.9909 22.6954 10 20 10C16.0909 10 12.7091 12.2409 11.0636 15.5091L14.4045 18.1C15.1909 15.7364 17.3954 13.9773 20 13.9773Z"
            />
        </svg>
    );
}

/** Discord's symbol in Blurple (discord.com/branding). */
function DiscordMark(props: SVGProps<SVGSVGElement>): ReactNode {
    return (
        <svg viewBox="0 0 64 48" {...props}>
            <path
                fill="#5865F2"
                d="M40.575 0C39.9562 1.09866 39.4006 2.2352 38.8954 3.397C34.0967 2.67719 29.2096 2.67719 24.3982 3.397C23.9057 2.2352 23.3374 1.09866 22.7186 0C18.2104 0.770324 13.8157 2.12155 9.64839 4.02841C1.38951 16.2652 -0.845688 28.1863 0.265599 39.9432C5.10222 43.517 10.5197 46.2447 16.2909 47.9874C17.5916 46.2447 18.7407 44.3883 19.7257 42.4562C17.8568 41.7616 16.0509 40.8903 14.3208 39.88C14.7755 39.5517 15.2175 39.2107 15.6468 38.8824C25.7873 43.6559 37.5316 43.6559 47.6847 38.8824C48.1141 39.236 48.5561 39.577 49.0107 39.88C47.2806 40.9029 45.4748 41.7616 43.5931 42.4688C44.5781 44.4009 45.7273 46.2573 47.028 48C52.7991 46.2573 58.2167 43.5422 63.0533 39.9684C64.3666 26.3299 60.8055 14.5099 53.6452 4.04104C49.4905 2.13418 45.0959 0.782952 40.5876 0.0252565L40.575 0ZM21.1401 32.7072C18.0209 32.7072 15.4321 29.8785 15.4321 26.3804C15.4321 22.8824 17.9199 20.041 21.1275 20.041C24.3351 20.041 26.886 22.895 26.8354 26.3804C26.7849 29.8658 24.3224 32.7072 21.1401 32.7072ZM42.1788 32.7072C39.047 32.7072 36.4834 29.8785 36.4834 26.3804C36.4834 22.8824 38.9712 20.041 42.1788 20.041C45.3864 20.041 47.9246 22.895 47.8741 26.3804C47.8236 29.8658 45.3611 32.7072 42.1788 32.7072Z"
            />
        </svg>
    );
}

/** The X logo in black (Simple Icons; X's own brand toolkit page blocks automated access). */
function XMark(props: SVGProps<SVGSVGElement>): ReactNode {
    return (
        <svg viewBox="0 0 24 24" {...props}>
            <path
                fill="currentColor"
                d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"
            />
        </svg>
    );
}

const providers: Record<
    SocialProviderName,
    { label: string; Mark: (props: SVGProps<SVGSVGElement>) => ReactNode }
> = {
    google: { label: 'Google', Mark: GoogleMark },
    discord: { label: 'Discord', Mark: DiscordMark },
    x: { label: 'X', Mark: XMark },
};

/**
 * "Continue with …" buttons for the configured social providers, then an "or"
 * line above the email form. Plain links, not Inertia's Link: signing in needs
 * a full browser redirect to the provider.
 */
export function SocialButtons({
    providers: enabled,
}: {
    providers: SocialProviderName[];
}): ReactNode {
    if (enabled.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-3">
            {enabled.map((name) => {
                const { label, Mark } = providers[name];

                return (
                    <Button
                        key={name}
                        asChild
                        variant="outline"
                        className="w-full border-line bg-bg hover:bg-bg-2 hover:text-ink"
                    >
                        <a href={redirect.url({ provider: name })}>
                            <Mark aria-hidden="true" className="size-5" />
                            Continue with {label}
                        </a>
                    </Button>
                );
            })}
            <div className="mt-2 flex items-center gap-3 text-sm text-muted">
                <span className="h-px flex-1 bg-line" />
                or
                <span className="h-px flex-1 bg-line" />
            </div>
        </div>
    );
}
