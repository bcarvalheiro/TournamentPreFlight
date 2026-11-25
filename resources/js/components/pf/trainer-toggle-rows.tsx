import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { create } from '@/routes/profiles';
import { update } from '@/routes/events/attendance';
import type { OwnAttendance } from '@/types';

type Trainer = { id: number; name: string };

type TrainerToggleRowsProps = {
    eventId: number;
    /** "Carta Alta, Sun 29 Nov", for the toast. */
    place: string;
    trainers: Trainer[];
    goingIds: number[];
    /** The props the page reloads after a save (Inertia partial reload). */
    reload: string[];
};

const rowClasses =
    'flex min-h-12 w-full cursor-pointer items-center justify-between gap-3 rounded-md border-[1.5px] border-line-strong bg-white px-3.5 text-left font-sans disabled:cursor-default disabled:opacity-60';

/** Going: red outline with a light red tint (design.md, ProfilePicker). */
const rowGoingClasses = 'border-red bg-red/5';

/**
 * The account's trainers as big toggle rows (design.md, Event page; M09):
 * each tap saves that trainer's answer at once (no Save button), then the
 * page reloads the props it names and a toast confirms. Everything comes in
 * as props, so the rows work on any page (decisions.md, 25 Nov).
 */
export function TrainerToggleRows({
    eventId,
    place,
    trainers,
    goingIds,
    reload,
}: TrainerToggleRowsProps): ReactNode {
    const [savingId, setSavingId] = useState<number | null>(null);
    const solo = trainers.length === 1;

    function toggle(trainer: Trainer, going: boolean): void {
        const who = solo ? "You're" : `${trainer.name} is`;

        router.put(
            update.url(eventId),
            { profiles: { [trainer.id]: going } },
            {
                only: reload,
                preserveScroll: true,
                onStart: () => setSavingId(trainer.id),
                onFinish: () => setSavingId(null),
                // Only when the answer really changed: a save sent back (too many
                // tries) also ends here, with its own toast (M08).
                onSuccess: (page) => {
                    const own = page.props.own as OwnAttendance | null;
                    const nowGoing = (own?.going[eventId] ?? []).includes(
                        trainer.id,
                    );

                    if (nowGoing === going) {
                        toast.success(
                            going
                                ? `${who} going to ${place}`
                                : `${who} no longer going to ${place}`,
                        );
                    }
                },
                onError: (errors) =>
                    toast.error(errors.event ?? 'Could not save. Try again.'),
            },
        );
    }

    return (
        <div className="flex flex-col gap-2">
            <ul className="flex flex-col gap-2">
                {trainers.map((trainer) => {
                    const isGoing = goingIds.includes(trainer.id);

                    return (
                        <li key={trainer.id}>
                            <button
                                type="button"
                                aria-pressed={isGoing}
                                disabled={savingId !== null}
                                onClick={() => toggle(trainer, !isGoing)}
                                className={cn(
                                    rowClasses,
                                    isGoing && rowGoingClasses,
                                )}
                            >
                                <span
                                    className={cn(
                                        'text-base text-ink',
                                        isGoing && 'font-bold',
                                    )}
                                >
                                    {trainer.name}
                                </span>
                                {/* Seen, not read: aria-pressed already tells screen readers. */}
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'text-sm whitespace-nowrap',
                                        isGoing
                                            ? 'font-bold text-red-text'
                                            : 'text-muted',
                                    )}
                                >
                                    {isGoing ? 'Going ✓' : "I'm going"}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
            <Link
                href={create()}
                className="inline-flex min-h-10 w-fit items-center font-sans text-sm font-semibold text-ink underline decoration-red decoration-2 underline-offset-4"
            >
                + Add a trainer
            </Link>
        </div>
    );
}
