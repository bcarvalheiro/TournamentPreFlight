import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { EventDetails, formatEntryFee } from '@/components/pf/event-details';
import type { EventPage } from '@/types';

function event(overrides: Partial<EventPage> = {}): EventPage {
    return {
        id: 7,
        url: 'https://preflight.test/events/7-winter-cup',
        source: 'pokedata',
        displayId: '26-11-001729',
        type: 'cup',
        title: 'Winter Cup',
        shopName: 'Carta Alta',
        address: 'Rua das Flores 12',
        district: 'Lisboa',
        city: 'Lisboa',
        latitude: null,
        longitude: null,
        startsAt: '2026-11-29T10:00:00Z',
        endsAt: '2026-11-29T16:00:00Z',
        officialUrl: 'https://example.test/tournaments/26-11-001729/',
        costCents: 1500,
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

const html = (overrides: Partial<EventPage> = {}) =>
    renderToStaticMarkup(<EventDetails event={event(overrides)} />);

/** The visible words, tags removed. */
const text = (markup: string) =>
    markup.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');

describe('EventDetails', () => {
    it('says when the event is, with the end marked as an estimate', () => {
        const words = text(html());

        expect(words).toContain('Sunday 29 November 2026');
        expect(words).toContain('Starts 10:00 · ends around 16:00 (estimate)');
    });

    it('names the format without the brand (rule 7)', () => {
        const words = text(html());

        expect(words).toContain('TCG · League Cup');
        expect(words).not.toMatch(/Pok[eé]mon/);
    });

    it('shows the entry fee in Portuguese style, only when known', () => {
        expect(text(html())).toMatch(/Entry fee 15\s€/);
        expect(text(html({ costCents: null }))).not.toContain('Entry fee');
    });

    it('shows the tournament ID only when there is one', () => {
        expect(text(html())).toContain('26-11-001729');
        expect(text(html({ displayId: null }))).not.toContain('Tournament ID');
    });

    it('links to the official page in a new tab, only when known', () => {
        expect(html()).toMatch(
            /<a[^>]*href="https:\/\/example\.test\/tournaments\/26-11-001729\/"[^>]*target="_blank"[^>]*>.*Official page/s,
        );
        expect(text(html({ officialUrl: null }))).not.toContain(
            'Official page',
        );
    });

    it('offers to share the event\'s "Going?" link', () => {
        expect(html()).toMatch(/<button[^>]*>.*Share<\/button>/s);
    });

    it('leaves Directions to the shop column', () => {
        expect(text(html())).not.toContain('Directions');
    });

    it('reminds that it is a headcount, not a registration', () => {
        expect(text(html())).toContain('A headcount, not a registration.');
    });

    it('says where the event comes from', () => {
        expect(text(html())).toContain('Source: pokedata');
        expect(text(html({ source: 'player' }))).toContain('Added by a player');
    });
});

describe('formatEntryFee', () => {
    // pt-PT puts the euro sign after the amount, with a decimal comma (decisions.md, 25 Nov).
    it('writes whole euros without cents', () => {
        expect(formatEntryFee(1500)).toMatch(/^15\s€$/);
    });

    it('writes cents with a decimal comma', () => {
        expect(formatEntryFee(650)).toMatch(/^6,50\s€$/);
    });

    it('says "Free" for nothing to pay', () => {
        expect(formatEntryFee(0)).toBe('Free');
    });
});
