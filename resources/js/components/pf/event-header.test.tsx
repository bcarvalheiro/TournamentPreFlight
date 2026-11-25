import { renderToStaticMarkup } from 'react-dom/server';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { EventHeader } from '@/components/pf/event-header';
import type { EventPage } from '@/types';

function event(overrides: Partial<EventPage> = {}): EventPage {
    return {
        id: 7,
        url: 'https://preflight.test/events/7-winter-cup',
        displayId: '26-11-001729',
        type: 'cup',
        title: 'Winter Cup',
        shopName: 'Carta Alta',
        address: 'Rua das Flores 12',
        district: 'Lisboa',
        city: 'Lisboa',
        startsAt: '2026-11-29T10:00:00Z',
        endsAt: '2026-11-29T16:00:00Z',
        officialUrl: null,
        goingCount: 0,
        confirmedCount: 0,
        isOngoing: false,
        hasEnded: false,
        isCancelled: false,
        possiblyCancelled: false,
        canAnswer: true,
        ...overrides,
    };
}

function render(overrides: Partial<EventPage> = {}): string {
    return renderToStaticMarkup(<EventHeader event={event(overrides)} />);
}

/** The visible words, tags removed. */
function text(html: string): string {
    return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
}

describe('EventHeader', () => {
    // Wed 25 Nov 2026, 12:00 in Lisbon (UTC in winter).
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-11-25T12:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('shows the title, shop, city, day, start and how soon', () => {
        const html = render();

        expect(html).toMatch(/<h1[^>]*>Winter Cup<\/h1>/);
        expect(text(html)).toContain('Carta Alta · Lisboa');
        expect(text(html)).toContain('Sun 29 Nov');
        expect(text(html)).toContain('10:00');
        expect(text(html)).toContain('In 4 days');
    });

    it('says "Happening now" for an ongoing event', () => {
        expect(text(render({ isOngoing: true }))).toContain('Happening now');
    });

    it('says the event has ended instead of how soon (M09 AC2)', () => {
        const words = text(
            render({
                startsAt: '2026-11-24T10:00:00Z',
                hasEnded: true,
                canAnswer: false,
            }),
        );

        expect(words).toContain('This event has ended');
        expect(words).not.toMatch(/In \d+ days|Today|Happening now/);
    });

    it('says a cancelled event is cancelled', () => {
        expect(text(render({ isCancelled: true, canAnswer: false }))).toContain(
            'Cancelled',
        );
    });

    it('warns when the event may be cancelled', () => {
        expect(text(render({ possiblyCancelled: true }))).toContain(
            'Possibly cancelled, check with the shop',
        );
        expect(text(render())).not.toContain('Possibly cancelled');
    });

    it('links back to all events', () => {
        expect(render()).toMatch(/<a[^>]*href="\/"[^>]*>[^<]*All events/);
    });
});
