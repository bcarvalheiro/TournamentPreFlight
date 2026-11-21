import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { FilterBar } from '@/components/pf/filter-bar';
import type { HomeFilters, ShopFilter } from '@/types';

const noFilters: HomeFilters = {
    type: [],
    shop: [],
    when: 'any',
    from: null,
    to: null,
};

const shops: ShopFilter[] = [
    { id: 3, name: 'Carta Alta', city: 'Porto', eventCount: 2 },
];

function render(
    filters: Partial<HomeFilters> = {},
    counts = { listed: 12, matched: 12, all: 29 },
): string {
    return renderToStaticMarkup(
        <FilterBar
            filters={{ ...noFilters, ...filters }}
            shops={shops}
            listed={counts.listed}
            totals={{ matched: counts.matched, all: counts.all }}
        />,
    );
}

/** The decoded link of the chip that removes `name` ("/?shop[]=3&when=week"). */
function removeLink(markup: string, name: string): string {
    const tag = markup.match(
        new RegExp(`<a[^>]*aria-label="Remove ${name} filter"[^>]*>`),
    )?.[0];
    const href = tag?.match(/href="([^"]*)"/)?.[1] ?? '';

    return decodeURIComponent(href.replaceAll('&amp;', '&'));
}

describe('FilterBar', () => {
    it('shows how many filters are on in the Filters badge', () => {
        expect(render({ type: ['cup'], shop: [3], when: 'week' })).toMatch(
            /Filters.*>3</,
        );
        expect(render()).not.toMatch(/Filters.*>\d+</);
    });

    it('offers "Any time" only when no date filter is set', () => {
        expect(render()).toContain('Any time');
        expect(render({ when: '7' })).not.toContain('Any time');
    });

    it('says "First 25 of 40 events" when the cap cuts the list', () => {
        expect(render({}, { listed: 25, matched: 40, all: 50 })).toContain(
            'First 25 of 40 events',
        );
    });

    it('says "12 of 29 events" when every match is listed', () => {
        expect(render()).toContain('12 of 29 events');
    });

    it('shows one chip per filter, each linking to the page without it', () => {
        const markup = render({ type: ['cup'], shop: [3], when: 'week' });

        expect(removeLink(markup, 'Cup')).toBe('/?shop[]=3&when=week');
        expect(removeLink(markup, 'Carta Alta')).toBe('/?type[]=cup&when=week');
        expect(removeLink(markup, 'This week')).toBe('/?type[]=cup&shop[]=3');
    });

    it.each([
        [{ when: 'week' }, 'This week'],
        [{ when: 'weekend' }, 'This weekend'],
        [{ when: '7' }, 'Next 7 days'],
        [{ when: '30' }, 'Next 30 days'],
        [
            { when: 'custom', from: '2026-12-05', to: '2026-12-12' },
            'Sat 5 Dec – Sat 12 Dec',
        ],
        [{ when: 'custom', from: '2026-12-05' }, 'From Sat 5 Dec'],
        [{ when: 'custom', to: '2026-12-12' }, 'Until Sat 12 Dec'],
    ] as [Partial<HomeFilters>, string][])(
        'names the date chip %j as "%s"',
        (filters, label) => {
            expect(render(filters)).toContain(label);
        },
    );
});
