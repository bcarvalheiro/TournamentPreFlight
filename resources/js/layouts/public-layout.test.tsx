import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import PublicLayout from '@/layouts/public-layout';

// No Inertia app runs here, so usePage() is replaced by a fake page whose
// props each test sets; Link stays the real one.
const page = vi.hoisted(() => ({
    url: '/',
    component: 'home',
    props: {
        auth: {
            user: null as { id: number } | null,
            profilesCount: null as number | null,
        },
    },
}));

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    usePage: () => page,
}));

function render(): string {
    return renderToStaticMarkup(
        <PublicLayout>
            <p>the page</p>
        </PublicLayout>,
    );
}

function signIn(profilesCount: number): void {
    page.props.auth = { user: { id: 1 }, profilesCount };
}

describe('PublicLayout', () => {
    beforeEach(() => {
        page.component = 'home';
        page.props.auth = { user: null, profilesCount: null };
    });

    it('offers a guest sign in and sign up', () => {
        const markup = render();

        expect(markup).toContain('Sign in');
        expect(markup).toContain('Sign up');
        expect(markup).not.toContain('Trainers');
    });

    // M07: the account reaches its trainers and Settings (Security) from every page.
    it('links a signed-in account to its trainers and settings', () => {
        signIn(2);
        const markup = render();

        expect(markup).toMatch(
            /<a[^>]*href="\/profiles"[^>]*>Trainers \(2\)<\/a>/,
        );
        expect(markup).toMatch(/<a[^>]*href="\/settings"[^>]*>Settings<\/a>/);
        expect(markup).toContain('Sign out');
        expect(markup).not.toContain('to say you&#x27;re going');
    });

    // M07 AC4: sign-in lands on the page, and a notice leads to "Add a trainer".
    it('asks an account without trainers to add one', () => {
        signIn(0);

        expect(render()).toMatch(
            /Add a trainer to say you&#x27;re going.*<a[^>]*href="\/profiles\/create"[^>]*>Add a trainer<\/a>/s,
        );
    });

    it('hides the notice on the "Add a trainer" page itself', () => {
        signIn(0);
        page.component = 'profiles/create';

        expect(render()).not.toContain('to say you&#x27;re going');
    });
});
