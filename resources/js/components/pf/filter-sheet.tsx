import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactElement, ReactNode } from 'react';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DATE_PRESETS, NO_FILTERS, urlFor } from '@/lib/home-filters';
import { lisbonDate } from '@/lib/time';
import {
    TOURNAMENT_TYPE_NAMES,
    TOURNAMENT_TYPES,
} from '@/lib/tournament-types';
import type { HomeFilters, ShopFilter } from '@/types';

type FilterSheetProps = {
    filters: HomeFilters;
    shops: ShopFilter[];
    /** Events matching the filters, before the cap of 25 (`totals.matched`). */
    matched: number;
    /** The Filters pill; focus returns to it when the sheet closes. */
    trigger: ReactElement;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

/** Reloads the list for these filters, keeping the sheet open (decisions.md, apply as you tick). */
function apply(filters: HomeFilters): void {
    router.get(
        urlFor(filters),
        {},
        {
            preserveState: true, // the sheet stays open
            preserveScroll: true,
            replace: true, // ticking does not fill the Back button history
            only: ['events', 'totals', 'shops', 'filters'], // up next is left alone
        },
    );
}

/** The list without `value` if it is in it, with it otherwise. */
function toggled<T>(list: T[], value: T): T[] {
    return list.includes(value)
        ? list.filter((item) => item !== value)
        : [...list, value];
}

const option =
    'flex h-11 cursor-pointer items-center justify-center gap-2 rounded-button border-[1.5px] border-line bg-bg px-3 text-sm font-semibold aria-pressed:border-ink aria-pressed:bg-ink aria-pressed:text-white';

const sectionTitle = 'mb-2 font-display text-lg font-bold uppercase';

/**
 * The filter sheet (design.md, Filter sheet): When, Type and Host shop.
 * Every change reloads the list at once; "Show n events" just closes it.
 */
export function FilterSheet({
    filters,
    shops,
    matched,
    trigger,
    open,
    onOpenChange,
}: FilterSheetProps): ReactNode {
    const [picking, setPicking] = useState(filters.when === 'custom');
    const today = lisbonDate();
    const showDates = picking || filters.when === 'custom';

    function pickDay(key: 'from' | 'to', day: string): void {
        apply({ ...filters, when: 'custom', [key]: day || null });
    }

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={onOpenChange}
            trigger={trigger}
            title="Filters"
            footer={
                <>
                    <Button
                        variant="outline"
                        onClick={() => {
                            setPicking(false);
                            apply(NO_FILTERS);
                        }}
                    >
                        Clear all
                    </Button>
                    <Button onClick={() => onOpenChange(false)}>
                        Show {matched} {matched === 1 ? 'event' : 'events'}
                    </Button>
                </>
            }
        >
            <div className="max-h-[55vh] space-y-5 overflow-y-auto pb-1">
                <fieldset>
                    <legend className={sectionTitle}>When</legend>
                    <div className="grid grid-cols-2 gap-2">
                        {DATE_PRESETS.map(({ when, name }) => (
                            <button
                                key={when}
                                type="button"
                                aria-pressed={
                                    !showDates && filters.when === when
                                }
                                onClick={() => {
                                    setPicking(false);
                                    apply({
                                        ...filters,
                                        when,
                                        from: null,
                                        to: null,
                                    });
                                }}
                                className={option}
                            >
                                {name}
                            </button>
                        ))}
                        <button
                            type="button"
                            aria-pressed={showDates}
                            onClick={() => setPicking(true)}
                            className={option}
                        >
                            Pick dates
                        </button>
                    </div>
                    {showDates && (
                        // min: no past days, and To never before From (the server drops them too).
                        <div className="mt-3 grid grid-cols-2 gap-2 text-sm font-semibold">
                            <label className="flex flex-col gap-1">
                                From
                                <input
                                    type="date"
                                    min={today}
                                    value={filters.from ?? ''}
                                    onChange={(e) =>
                                        pickDay('from', e.target.value)
                                    }
                                    className="h-10 rounded-button border border-line px-2 font-normal"
                                />
                            </label>
                            <label className="flex flex-col gap-1">
                                To
                                <input
                                    type="date"
                                    min={filters.from ?? today}
                                    value={filters.to ?? ''}
                                    onChange={(e) =>
                                        pickDay('to', e.target.value)
                                    }
                                    className="h-10 rounded-button border border-line px-2 font-normal"
                                />
                            </label>
                        </div>
                    )}
                </fieldset>

                <fieldset>
                    <legend className={sectionTitle}>Type</legend>
                    <div className="grid grid-cols-2 gap-2">
                        {TOURNAMENT_TYPES.map((type) => (
                            <button
                                key={type}
                                type="button"
                                data-testid={`type-${type}`}
                                aria-pressed={filters.type.includes(type)}
                                onClick={() =>
                                    apply({
                                        ...filters,
                                        type: toggled(filters.type, type),
                                    })
                                }
                                className={option}
                            >
                                <TournamentTypeIcon type={type} size={20} />
                                {TOURNAMENT_TYPE_NAMES[type]}
                            </button>
                        ))}
                    </div>
                </fieldset>

                <fieldset>
                    <legend className={sectionTitle}>Host shop</legend>
                    <ul className="divide-y divide-line">
                        {shops.map((shop) => (
                            <li key={shop.id}>
                                <label className="flex cursor-pointer items-center gap-3 py-2.5 text-sm">
                                    <Checkbox
                                        data-testid={`shop-${shop.id}`}
                                        checked={filters.shop.includes(shop.id)}
                                        onCheckedChange={() =>
                                            apply({
                                                ...filters,
                                                shop: toggled(
                                                    filters.shop,
                                                    shop.id,
                                                ),
                                            })
                                        }
                                    />
                                    <span className="flex-1">
                                        <span className="font-semibold">
                                            {shop.name}
                                        </span>{' '}
                                        <span className="text-muted">
                                            {shop.city}
                                        </span>
                                    </span>
                                    <span className="text-muted tabular-nums">
                                        {shop.eventCount}
                                    </span>
                                </label>
                            </li>
                        ))}
                    </ul>
                </fieldset>
            </div>
        </ResponsiveDialog>
    );
}
