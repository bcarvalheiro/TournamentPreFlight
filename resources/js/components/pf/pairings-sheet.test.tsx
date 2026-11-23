import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vite-plus/test';
import { PairingsSheet } from '@/components/pf/pairings-sheet';
import type { EventCard } from '@/types';

// No Inertia app runs here, so usePage() (read by GoingButton) is replaced
// by a fake page whose signed-in user each test sets; Link stays the real one.
const page = vi.hoisted(() => ({
    url: '/?type[]=cup',
    props: { auth: { user: null as { id: number } | null } },
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

function render(events: EventCard[], signedIn = false): string {
    page.props.auth.user = signedIn ? { id: 1 } : null;

    return renderToStaticMarkup(<PairingsSheet events={events} />);
}

describe('PairingsSheet', () => {
    it('shows each event with its shop, type, city and count', () => {
        const markup = render([card()]);

        expect(markup).toContain('Carta Alta');
        expect(markup).toContain('Cup · Porto');
        expect(markup).toContain(
            '12<span class="sr-only"> trainers going</span>',
        );
    });

    it('shows month, then weekday and day, then time (Lisbon), or NOW and LIVE while it runs', () => {
        expect(render([card()])).toMatch(/>Dec<.*>Sat 5<.*>10:00</);

        const live = render([card({ isOngoing: true })]);
        expect(live).toMatch(/NOW.*LIVE/);
        expect(live).not.toContain('10:00');
    });

    it('sends signed-out players to sign in from "I\'m going", back to this page', () => {
        expect(render([card()])).toMatch(
            /<form[^>]*action="\/events\/1\/going"[^>]*method="post"[^>]*>.*<input type="hidden" name="return_to" value="\/\?type\[\]=cup"\/>.*<button type="submit"[^>]*>I(&#x27;|')m going<\/button><\/form>/,
        );
    });

    it('gives signed-in players the same "I\'m going" form', () => {
        // M06: the server decides what comes next (verify, Player ID, or the
        // intent handed back); M08 turns it into a toggle for ready players.
        expect(render([card()], true)).toMatch(
            /<form[^>]*action="\/events\/1\/going"[^>]*method="post"[^>]*>.*<button type="submit"[^>]*>I(&#x27;|')m going<\/button><\/form>/,
        );
    });

    it('says so when nothing is coming up', () => {
        expect(render([])).toContain('Nothing on the calendar yet.');
    });
});
