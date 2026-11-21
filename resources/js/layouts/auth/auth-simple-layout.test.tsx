import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import AuthSimpleLayout from '@/layouts/auth/auth-simple-layout';

// No Inertia app runs here, so usePage() (read by the band's navbar) is
// replaced by a fake page with nobody signed in; Link stays the real one.
const page = vi.hoisted(() => ({
    url: '/login',
    props: { auth: { user: null } },
}));

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    usePage: () => page,
}));

function render(): string {
    return renderToStaticMarkup(
        <AuthSimpleLayout title="Sign in" description="Welcome back">
            <form>the form</form>
        </AuthSimpleLayout>,
    );
}

describe('AuthSimpleLayout', () => {
    it('shows the title, description and form in a card', () => {
        const markup = render();

        expect(markup).toMatch(
            /data-slot="card".*Sign in.*Welcome back.*the form/s,
        );
    });

    // CLAUDE.md rule 7: every page footer carries the non-affiliation notice.
    it('carries the footer notice', () => {
        expect(render()).toContain('Independent fan project.');
    });
});
