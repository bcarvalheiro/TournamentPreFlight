import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import { formatDay } from '@/lib/time';
import { TOURNAMENT_TYPE_NAMES } from '@/lib/tournament-types';
import { home } from '@/routes';
import type { HomeFilters, ShopFilter } from '@/types';

type FilterBarProps = {
    filters: HomeFilters;
    shops: ShopFilter[];
    /** How many events the list shows (at most 25). */
    listed: number;
    totals: { matched: number; all: number };
    /** Opens the filter sheet. */
    onOpen: () => void;
};

const PRESET_NAMES: Record<string, string> = {
    week: 'This week',
    weekend: 'This weekend',
    '7': 'Next 7 days',
    '30': 'Next 30 days',
};

const pill =
    'inline-flex h-9 shrink-0 cursor-pointer items-center gap-2 rounded-full border-[1.5px] border-ink px-3.5 text-sm font-semibold whitespace-nowrap';

const chip =
    'inline-flex h-8 shrink-0 items-center gap-1.5 rounded-full border bg-bg pr-2.5 pl-3 text-sm font-semibold whitespace-nowrap after:text-base after:text-muted after:content-["×"]';

/** A picked day ("2026-12-05") as "Sat 5 Dec"; midday UTC is the same day in Lisbon. */
function pickedDay(day: string): string {
    return formatDay(`${day}T12:00:00Z`);
}

/** "This week", "Sat 5 Dec – Sat 12 Dec", "From Sat 5 Dec", "Until Sat 12 Dec". */
function dateLabel({ when, from, to }: HomeFilters): string {
    if (when !== 'custom') {
        return PRESET_NAMES[when];
    }

    if (from && to) {
        return `${pickedDay(from)} – ${pickedDay(to)}`;
    }

    return from ? `From ${pickedDay(from)}` : `Until ${pickedDay(to ?? '')}`;
}

/** The home page URL for these filters; Wayfinder leaves out empty values. */
function urlFor(filters: HomeFilters): string {
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

/**
 * The sticky bar above the agenda (design.md, Sticky filter bar): the Filters
 * pill with the number of active filters, "Any time ▾" while no date is set,
 * one chip per active filter (each links to the page without it) and the count.
 */
export function FilterBar({
    filters,
    shops,
    listed,
    totals,
    onOpen,
}: FilterBarProps): ReactNode {
    const hasDate = filters.when !== 'any';
    const active =
        filters.type.length + filters.shop.length + (hasDate ? 1 : 0);
    const noDate = { when: 'any', from: null, to: null } as const;

    return (
        // -mx-4 px-4: the bar's background reaches the page edges on phones.
        <div className="sticky top-0 z-10 -mx-4 mb-4 border-b border-line bg-bg-2/95 px-4 backdrop-blur-sm">
            {/* Scrolls sideways on phones; [scrollbar-width:none] hides the bar (no Tailwind utility). */}
            <div className="mx-auto flex max-w-[760px] [scrollbar-width:none] items-center gap-2 overflow-x-auto py-2.5">
                <button
                    type="button"
                    onClick={onOpen}
                    className={`${pill} bg-ink text-white`}
                >
                    Filters
                    {active > 0 && (
                        <span className="rounded-full bg-red px-1.75 text-xs">
                            {active}
                        </span>
                    )}
                </button>
                {hasDate ? (
                    <Link
                        href={urlFor({ ...filters, ...noDate })}
                        aria-label="Remove date filter"
                        className={`${chip} border-ink`}
                    >
                        {dateLabel(filters)}
                    </Link>
                ) : (
                    <button
                        type="button"
                        onClick={onOpen}
                        className={`${pill} bg-bg`}
                    >
                        Any time ▾
                    </button>
                )}
                {filters.type.map((type) => (
                    <Link
                        key={type}
                        href={urlFor({
                            ...filters,
                            type: filters.type.filter((t) => t !== type),
                        })}
                        aria-label={`Remove ${TOURNAMENT_TYPE_NAMES[type]} filter`}
                        className={`${chip} border-line`}
                    >
                        <TournamentTypeIcon type={type} />
                        {TOURNAMENT_TYPE_NAMES[type]}
                    </Link>
                ))}
                {filters.shop.map((id) => {
                    const name =
                        shops.find((shop) => shop.id === id)?.name ?? 'Shop';

                    return (
                        <Link
                            key={id}
                            href={urlFor({
                                ...filters,
                                shop: filters.shop.filter((s) => s !== id),
                            })}
                            aria-label={`Remove ${name} filter`}
                            className={`${chip} border-line`}
                        >
                            {name}
                        </Link>
                    );
                })}
                <span className="ml-auto shrink-0 pl-2 text-sm whitespace-nowrap text-muted">
                    {totals.matched > listed
                        ? `First ${listed} of ${totals.matched}`
                        : `${listed} of ${totals.all}`}{' '}
                    events
                </span>
            </div>
        </div>
    );
}
