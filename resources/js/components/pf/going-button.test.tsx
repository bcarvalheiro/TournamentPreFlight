import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import { GoingButton } from '@/components/pf/going-button';
import type { EventCard, OwnAttendance } from '@/types';

// No Inertia app runs here, so usePage() is replaced by a fake page whose
// `own` prop (M08: the account's trainers and answers) each test sets.
// Each attribute is checked on its own, so reordering JSX props is harmless.
const page = vi.hoisted(() => ({
    url: '/',
    props: { own: null as OwnAttendance | null },
}));

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    usePage: () => page,
}));

const event: EventCard = {
    id: 1,
    url: 'https://preflight.test/events/1-casual',
    displayId: null,
    type: 'casual',
    title: 'Casual at Carta Alta',
    shopName: 'Carta Alta',
    city: 'Porto',
    startsAt: '2026-12-05T10:00:00Z',
    endsAt: '2026-12-05T12:00:00Z',
    isOngoing: false,
    goingCount: 3,
    possiblyCancelled: false,
};

function render(own: OwnAttendance | null): string {
    page.props.own = own;

    return renderToStaticMarkup(<GoingButton event={event} />);
}

const ana = { id: 7, name: 'Ana' };
const tiago = { id: 9, name: 'Tiago' };

/** Screen readers hear which event a button is for (review fix, WCAG 2.5.3). */
const forEvent =
    '<span class="sr-only"> to Carta Alta, Sat 5 Dec</span></button>';

describe('GoingButton', () => {
    it('gives a guest the pending-intent form (M06)', () => {
        const markup = render(null);

        expect(markup).toContain('<form');
        expect(markup).toContain('action="/events/1/going"');
        expect(markup).toContain('method="post"');
        expect(markup).toContain(`>I&#x27;m going${forEvent}`);
    });

    it('lets a solo player who is not going tap "I\'m going"', () => {
        const markup = render({ trainers: [ana], going: [] });

        expect(markup).not.toContain('<form');
        expect(markup).toContain('type="button"');
        expect(markup).toContain('aria-pressed="false"');
        expect(markup).toContain(`>I&#x27;m going${forEvent}`);
    });

    it('shows a going solo player "Going ✓", pressed and outlined', () => {
        const markup = render({ trainers: [ana], going: { 1: [7] } });

        expect(markup).toContain('aria-pressed="true"');
        expect(markup).toContain('bg-transparent');
        expect(markup).toContain(`>Going ✓${forEvent}`);
    });

    it('lets a family with nobody going open the picker from "I\'m going"', () => {
        const markup = render({ trainers: [ana, tiago], going: [] });

        expect(markup).not.toContain('<form');
        expect(markup).toContain('aria-haspopup="dialog"');
        expect(markup).not.toContain('bg-transparent');
        expect(markup).toContain(`>I&#x27;m going${forEvent}`);
    });

    it('shows a family with two going "Going (2)", outlined', () => {
        const markup = render({ trainers: [ana, tiago], going: { 1: [7, 9] } });

        expect(markup).toContain('aria-haspopup="dialog"');
        expect(markup).toContain('bg-transparent');
        expect(markup).toContain(`>Going (2)${forEvent}`);
    });
});
