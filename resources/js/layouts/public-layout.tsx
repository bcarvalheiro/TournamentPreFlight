import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import AppLogo from '@/components/pf/app-logo';
import { home, login, logout, register, settings } from '@/routes';
import { create, index as profilesIndex } from '@/routes/profiles';

type PublicLayoutProps = {
    children: ReactNode;
    /**
     * Optional hero inside the dark band (the home page uses it). Pages pass it
     * as a static layout prop (Page.layout = [PublicLayout, { hero: <Hero /> }]); without it the band is navbar only.
     */
    hero?: ReactNode;
};

const navLink =
    'inline-flex h-10 items-center rounded-lg px-1 text-[0.8rem] font-semibold whitespace-nowrap text-board-fg hover:bg-white/10 sm:px-2.5 sm:text-[0.88rem]';

/** The playmat band: navbar, optional hero, two faint dashed card outlines as decoration. */
function Band({ hero }: { hero?: ReactNode }) {
    const { auth } = usePage().props;

    return (
        <div className="relative overflow-hidden bg-[radial-gradient(120%_90%_at_20%_0%,#2a3641_0%,var(--color-ink)_55%,#182028_100%)] text-board-fg">
            <span
                aria-hidden="true"
                className="pointer-events-none absolute -bottom-10 -left-8 h-42 w-30 -rotate-8 rounded-xl border-[1.5px] border-dashed border-white/7"
            />
            <span
                aria-hidden="true"
                className="pointer-events-none absolute -top-12 -right-6 h-42 w-30 rotate-12 rounded-xl border-[1.5px] border-dashed border-white/7"
            />
            <div className="relative mx-auto max-w-[1100px] px-4">
                <header className="flex h-[50px] items-center justify-between gap-2">
                    <Link
                        href={home()}
                        className="inline-flex h-10 items-center rounded-md text-white"
                    >
                        <AppLogo />
                    </Link>
                    <nav
                        aria-label="Account"
                        className="flex items-center sm:gap-1"
                    >
                        {auth.user ? (
                            <>
                                <Link
                                    href={profilesIndex()}
                                    className={navLink}
                                >
                                    Trainers ({auth.profilesCount})
                                </Link>
                                <Link href={settings()} className={navLink}>
                                    Settings
                                </Link>
                                <Link
                                    href={logout()}
                                    as="button"
                                    className={navLink}
                                >
                                    Sign out
                                </Link>
                            </>
                        ) : (
                            <>
                                <Link href={login()} className={navLink}>
                                    Sign in
                                </Link>
                                <Link
                                    href={register()}
                                    className="inline-flex h-10 items-center rounded-full bg-red px-3.5 text-[0.88rem] font-bold text-white"
                                >
                                    Sign up
                                </Link>
                            </>
                        )}
                    </nav>
                </header>
                {hero}
            </div>
        </div>
    );
}

/**
 * A signed-in account without trainers lands on the page as usual (M07,
 * decisions.md 23 Nov) and sees this notice on every page except "Add a
 * trainer" and the trainer list (which has its own empty state). An account
 * that has not verified its email is not shown it: adding a trainer starts
 * with verifying.
 */
function ProfileNotice() {
    const { props, component } = usePage();

    if (
        !props.auth.user?.email_verified_at ||
        props.auth.profilesCount !== 0 ||
        component === 'profiles/create' ||
        component === 'profiles/index'
    ) {
        return null;
    }

    return (
        <p className="mx-auto mt-4 w-full max-w-[1100px] px-4">
            <span className="block rounded-xl border border-red/20 bg-red/5 px-3.5 py-3 text-sm">
                <strong>Add a trainer to say you're going:</strong> you, or your
                child.{' '}
                <Link
                    href={create()}
                    className="inline-flex min-h-10 items-center font-semibold underline underline-offset-4"
                >
                    Add a trainer
                </Link>
            </span>
        </p>
    );
}

/** Footer links that have no page yet (privacy and terms in M14, add event in M11). */
const footerLinks = [
    { label: 'Privacy', href: '#' },
    { label: 'Terms', href: '#' },
    { label: 'Contact', href: '#' },
    { label: 'Add a missing event', href: '#' },
];

function Footer() {
    return (
        <footer className="mx-auto w-full max-w-[1100px] px-4 pt-6 pb-10 text-[0.8rem] text-muted">
            <div className="mx-auto max-w-[760px] space-y-2">
                <p>
                    Independent fan project. Not affiliated with, endorsed or
                    sponsored by Nintendo or The Pokémon Company.
                </p>
                <nav aria-label="Footer">
                    <ul className="flex flex-wrap gap-x-4">
                        {footerLinks.map((link) => (
                            <li key={link.label}>
                                <a
                                    href={link.href}
                                    className="inline-flex min-h-10 items-center font-semibold text-ink underline-offset-4 hover:underline"
                                >
                                    {link.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>
            </div>
        </footer>
    );
}

/**
 * Layout for every player-facing page: the dark band on top, the page, the footer.
 * Chosen per page name in app.tsx (Inertia "default layout").
 */
export default function PublicLayout({ children, hero }: PublicLayoutProps) {
    return (
        <div className="flex min-h-screen flex-col">
            <Band hero={hero} />
            <ProfileNotice />
            <main className="mx-auto w-full max-w-[1100px] flex-1 px-4 py-6">
                {children}
            </main>
            <Footer />
        </div>
    );
}
