import { home } from '@/routes';
import type { HomeFilters } from '@/types';

/** No filters at all: what "Clear all" goes back to. */
export const NO_FILTERS: HomeFilters = {
    type: [],
    shop: [],
    when: 'any',
    from: null,
    to: null,
};

/**
 * The date presets of the filter sheet, in its order (product.md, Home page).
 * A list, not an object: JavaScript puts keys like "7" first in an object.
 */
export const DATE_PRESETS: {
    when: Exclude<HomeFilters['when'], 'custom'>;
    name: string;
}[] = [
    { when: 'any', name: 'Any time' },
    { when: 'week', name: 'This week' },
    { when: 'weekend', name: 'This weekend' },
    { when: '7', name: 'Next 7 days' },
    { when: '30', name: 'Next 30 days' },
];

/** The home page URL for these filters; Wayfinder leaves out empty values. */
export function urlFor(filters: HomeFilters): string {
    return home.url({
        query: {
            type: filters.type,
            shop: filters.shop,
            when: filters.when === 'any' ? undefined : filters.when,
            from: filters.from,
            to: filters.to,
        },
    });
}
