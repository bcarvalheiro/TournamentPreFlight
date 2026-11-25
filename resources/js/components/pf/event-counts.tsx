import type { ReactNode } from 'react';

type EventCountsProps = {
    goingCount: number;
    confirmedCount: number;
    /** The answer controls under the count (M09 Slice 5): trainer rows or "I'm going". */
    children?: ReactNode;
};

/**
 * "Who's heading there" on the event page (design.md, Event page): the big
 * going count, the confirmed count once there is one (confirmation comes in
 * M12, so "(0 confirmed)" is never shown), and the headcount reminder.
 * Counts only, never names (CLAUDE.md rule 1).
 */
export function EventCounts({
    goingCount,
    confirmedCount,
    children,
}: EventCountsProps): ReactNode {
    return (
        // id="going": the phone's going bar jumps here.
        <section
            id="going"
            aria-labelledby="event-counts-title"
            className="scroll-mt-4"
        >
            <h2
                id="event-counts-title"
                className="mb-2 font-mono text-xs font-semibold tracking-widest text-muted uppercase"
            >
                Who's heading there
            </h2>
            <div
                data-test="event-counts"
                className="rounded-card border border-line bg-paper px-4 pt-3 pb-2.5 font-mono text-ink"
            >
                <p className="flex flex-wrap items-baseline gap-x-2.5 border-b-2 border-ink pb-3">
                    <span
                        data-test="going-count"
                        className="font-display text-6xl leading-none font-extrabold text-red tabular-nums"
                    >
                        {goingCount}
                    </span>
                    <span className="font-display text-xl font-bold tracking-wide uppercase">
                        {goingCount === 1 ? 'trainer' : 'trainers'} going
                    </span>
                    {confirmedCount > 0 && (
                        <span className="text-sm text-muted">
                            ({confirmedCount} confirmed)
                        </span>
                    )}
                </p>
                {children && <div className="pt-3">{children}</div>}
                <p className="pt-2.5 text-2xs tracking-widest text-muted uppercase">
                    Headcount, not registration · Names never shown
                </p>
            </div>
        </section>
    );
}
