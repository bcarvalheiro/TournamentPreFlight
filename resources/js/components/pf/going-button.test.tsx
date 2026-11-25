import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import { GoingButton } from '@/components/pf/going-button';
import type { EventCard, OwnAttendance } from '@/types';

// No Inertia app runs here, so usePage() is replaced by a fake page whose
// `own` prop (M08: the account's trainers and answers) each test sets.
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

describe('GoingButton', () => {
    it('gives a guest the pending-intent form (M06)', () => {
        const markup = render(null);

        expect(markup).toMatch(
            /<form[^>]*action="\/events\/1\/going"[^>]*method="post"/,
        );
        expect(markup).toContain('I&#x27;m going');
    });

    it('lets a solo player who is not going tap "I\'m going"', () => {
        const markup = render({ trainers: [ana], going: [] });

        expect(markup).not.toContain('<form');
        expect(markup).toMatch(
            /<button type="button" aria-pressed="false"[^>]*>I&#x27;m going<\/button>/,
        );
    });

    it('shows a going solo player "Going ✓", pressed and outlined', () => {
        const markup = render({ trainers: [ana], going: { 1: [7] } });

        expect(markup).toMatch(
            /<button type="button" aria-pressed="true"[^>]*bg-transparent[^>]*>Going ✓<\/button>/,
        );
    });

    it('lets a family with nobody going open the picker from "I\'m going"', () => {
        const markup = render({ trainers: [ana, tiago], going: [] });

        expect(markup).not.toContain('<form');
        expect(markup).toMatch(
            /<button type="button" aria-haspopup="dialog"[^>]*>I&#x27;m going<\/button>/,
        );
    });

    it('shows a family with two going "Going (2)", outlined', () => {
        const markup = render({ trainers: [ana, tiago], going: { 1: [7, 9] } });

        expect(markup).toMatch(
            /<button type="button" aria-haspopup="dialog"[^>]*bg-transparent[^>]*>Going \(2\)<\/button>/,
        );
    });
});
