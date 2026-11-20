import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import { Agenda } from '@/components/pf/agenda';
import type { EventCard } from '@/types';

// No Inertia app runs here, so usePage() (read by GoingButton) is replaced
// by a fake page with nobody signed in; Link stays the real one.
const page = vi.hoisted(() => ({ props: { auth: { user: null } } }));

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

function render(events: EventCard[], totalUpcoming = events.length): string {
    return renderToStaticMarkup(
        <Agenda events={events} totalUpcoming={totalUpcoming} />,
    );
}

describe('Agenda', () => {
    it('shows a header per Lisbon day with its events under it', () => {
        const markup = render([
            card({ id: 1, shopName: 'Carta Alta' }),
            card({
                id: 2,
                shopName: 'Dado Dourado',
                startsAt: '2026-12-06T10:00:00Z',
            }),
        ]);

        expect(markup).toMatch(
            /Sat 5 Dec.*Carta Alta.*Sun 6 Dec.*Dado Dourado/,
        );
    });

    it('offers Clear filters when the filters hide every event', () => {
        const markup = render([], 3);

        expect(markup).toContain('Nothing matches these filters.');
        expect(markup).toMatch(/<a[^>]*href="\/"[^>]*>Clear filters<\/a>/);
    });

    it('says the calendar is empty when nothing is upcoming', () => {
        const markup = render([], 0);

        expect(markup).toContain('Nothing on the calendar yet.');
        expect(markup).not.toContain('Clear filters');
    });

    it('shows the type legend from Common to Rare', () => {
        expect(render([card()])).toMatch(
            /Common.*Casual.*Prerelease.*Challenge.*Cup.*Rare/,
        );
    });

    it('shows the headcount disclaimer, also when nothing is listed', () => {
        const disclaimer = 'A headcount, not a registration.';

        expect(render([card()])).toContain(disclaimer);
        expect(render([], 0)).toContain(disclaimer);
    });
});
