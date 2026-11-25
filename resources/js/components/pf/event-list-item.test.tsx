import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { EventListItem } from '@/components/pf/event-list-item';
import type { EventCard, OwnAttendance } from '@/types';

// No Inertia app runs here, so usePage() (read by GoingButton) is replaced
// by a fake page with nobody signed in; Link stays the real one.
const page = vi.hoisted(() => ({
    url: '/?type[]=cup',
    props: {
        auth: { user: null },
        own: null as OwnAttendance | null,
    },
}));

vi.mock('@inertiajs/react', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@inertiajs/react')>()),
    usePage: () => page,
}));

function card(overrides: Partial<EventCard> = {}): EventCard {
    return {
        id: 1,
        displayId: '26-12-000123',
        type: 'cup',
        title: 'Cup at Carta Alta',
        shopName: 'Carta Alta',
        city: 'Porto',
        startsAt: '2026-12-05T10:00:00Z',
        endsAt: '2026-12-05T16:00:00Z',
        isOngoing: false,
        goingCount: 12,
        possiblyCancelled: false,
        ...overrides,
    };
}

function render(event: EventCard): string {
    return renderToStaticMarkup(<EventListItem event={event} />);
}

describe('EventListItem', () => {
    // A failing case must not leave its `own` behind for the next one.
    beforeEach(() => {
        page.props.own = null;
    });

    it('shows the shop, city, start time (Lisbon) and long type name, not the ID', () => {
        const markup = render(card());

        expect(markup).toContain('Carta Alta');
        expect(markup).toContain('Porto');
        expect(markup).toMatch(/Starts.*10:00/);
        expect(markup).toContain('League Cup');
        expect(markup).not.toContain('26-12-000123');
    });

    it('shows "Happening now" instead of the start time while it runs', () => {
        const markup = render(card({ isOngoing: true }));

        expect(markup).toContain('Happening now');
        expect(markup).not.toContain('10:00');
    });

    it('shows the going count, read as "12 trainers going" by screen readers', () => {
        expect(render(card())).toContain(
            '12<span class="sr-only"> trainers going</span>',
        );
    });

    it('warns only when the event may be cancelled', () => {
        const warning = 'Possibly cancelled, check with the shop';

        expect(render(card({ possiblyCancelled: true }))).toContain(warning);
        expect(render(card())).not.toContain(warning);
    });

    it('sends signed-out players to sign in from "I\'m going", back to this page', () => {
        expect(render(card())).toMatch(
            /<form[^>]*action="\/events\/1\/going"[^>]*method="post"[^>]*>.*<input type="hidden" name="return_to" value="\/\?type\[\]=cup"\/>.*<button type="submit"[^>]*>I(&#x27;|')m going<\/button><\/form>/,
        );
    });

    it('shows a family which of its trainers are going, oldest first', () => {
        page.props.own = {
            trainers: [
                { id: 7, name: 'Ana' },
                { id: 8, name: 'Rui' },
                { id: 9, name: 'Tiago' },
            ],
            going: { 1: [9, 7] },
        };

        expect(render(card())).toContain('You: Ana, Tiago');
    });

    it('shows no "You:" line to a solo player or a guest', () => {
        page.props.own = {
            trainers: [{ id: 7, name: 'Ana' }],
            going: { 1: [7] },
        };
        expect(render(card())).not.toContain('You:');

        page.props.own = null;
        expect(render(card())).not.toContain('You:');
    });
});
