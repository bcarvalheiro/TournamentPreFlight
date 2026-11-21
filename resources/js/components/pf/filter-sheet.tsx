import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactElement, ReactNode } from 'react';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { TournamentTypeIcon } from '@/components/pf/tournament-type-icon';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DATE_PRESETS,
    isUsableDay,
    NO_FILTERS,
    urlFor,
} from '@/lib/home-filters';
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

const sectionTitle =
    'mb-2 text-xs font-semibold tracking-wider text-muted uppercase';

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
    // A draft of the filters: each tick builds on the last tick, not on the
    // last server answer, so quick ticks are never lost. The typed dates are
    // kept apart so a half-typed day stays in its box.
    const [draft, setDraft] = useState(filters);
    const [days, setDays] = useState({
        from: filters.from ?? '',
        to: filters.to ?? '',
    });
    const [answered, setAnswered] = useState(filters);
    const [picking, setPicking] = useState(filters.when === 'custom');
    const today = lisbonDate();
    const showDates = picking || draft.when === 'custom';

    // A new server answer resets the draft (react.dev, "Adjusting some state when a prop changes").
    if (filters !== answered) {
        setAnswered(filters);
        setDraft(filters);
        setDays({ from: filters.from ?? '', to: filters.to ?? '' });
    }

    function change(next: HomeFilters): void {
        setDraft(next);
        apply(next);
    }

    function pickDay(key: 'from' | 'to', day: string): void {
        setDays({ ...days, [key]: day });

        if (day === '') {
            change({ ...draft, when: 'custom', [key]: null });
        } else if (
            isUsableDay(day, key === 'to' ? (draft.from ?? today) : today)
        ) {
            // A From after the chosen To clears To, which the server would drop anyway.
            const to =
                key === 'from' && draft.to && draft.to < day ? null : draft.to;
            change({ ...draft, when: 'custom', to, [key]: day });
        }
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
                            change(NO_FILTERS);
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
            <div className="space-y-5 pb-1">
                <fieldset>
                    <legend className={sectionTitle}>When</legend>
                    <div className="grid grid-cols-2 gap-2">
                        {DATE_PRESETS.map(({ when, name }) => (
                            <button
                                key={when}
                                type="button"
                                aria-pressed={!showDates && draft.when === when}
                                onClick={() => {
                                    setPicking(false);
                                    change({
                                        ...draft,
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
                                    value={days.from}
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
                                    min={draft.from ?? today}
                                    value={days.to}
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
                                aria-pressed={draft.type.includes(type)}
                                onClick={() =>
                                    change({
                                        ...draft,
                                        type: toggled(draft.type, type),
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
                                        checked={draft.shop.includes(shop.id)}
                                        onCheckedChange={() =>
                                            change({
                                                ...draft,
                                                shop: toggled(
                                                    draft.shop,
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
