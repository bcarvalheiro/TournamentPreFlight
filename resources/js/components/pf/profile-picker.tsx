import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactElement, ReactNode } from 'react';
import { toast } from 'sonner';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { Button } from '@/components/ui/button';
import { formatDay } from '@/lib/time';
import { cn } from '@/lib/utils';
import { update } from '@/routes/events/attendance';
import { create } from '@/routes/profiles';
import type { EventCard, OwnAttendance } from '@/types';

type ProfilePickerProps = {
    event: EventCard;
    /** The account's trainers, oldest first. */
    trainers: OwnAttendance['trainers'];
    /** Ids of the trainers going to this event now. */
    goingIds: number[];
    /** The button that opens the picker; none when the page opens it (`defaultOpen`). */
    trigger?: ReactElement;
    /** Open on first render: the page resumes a pending "I'm going" (M08). */
    defaultOpen?: boolean;
};

/** One trainer's row: the whole row is the button, at least 48px tall for thumbs. */
const rowClasses =
    'flex min-h-12 w-full cursor-pointer items-center justify-between gap-3 rounded-md border-[1.5px] border-line-strong bg-white px-3.5 text-left disabled:cursor-default disabled:opacity-60';

/** Picked: red outline with a light red tint, like a going slip button. */
const rowPickedClasses = 'border-red bg-red/5';

/**
 * "Who is going?" for a family account (design.md, ProfilePicker; M08): one
 * big toggle per trainer in the slips' language ("I'm going" / "Going ✓"),
 * then Save sends every choice in one request. The sheet opens with the saved
 * answers each time.
 */
export function ProfilePicker({
    event,
    trainers,
    goingIds,
    trigger,
    defaultOpen = false,
}: ProfilePickerProps): ReactNode {
    const [open, setOpen] = useState(defaultOpen);
    const [checked, setChecked] = useState<number[]>(goingIds);
    const [saving, setSaving] = useState(false);
    const place = `${event.shopName}, ${formatDay(event.startsAt)}`;

    function openChange(isOpen: boolean): void {
        if (isOpen) {
            setChecked(goingIds);
        }

        setOpen(isOpen);
    }

    function toggle(trainerId: number): void {
        setChecked((current) =>
            current.includes(trainerId)
                ? current.filter((id) => id !== trainerId)
                : [...current, trainerId],
        );
    }

    function save(): void {
        router.put(
            update.url(event.id),
            {
                profiles: Object.fromEntries(
                    trainers.map((trainer) => [
                        trainer.id,
                        checked.includes(trainer.id),
                    ]),
                ),
            },
            {
                only: ['events', 'upNext', 'own'],
                preserveScroll: true,
                onStart: () => setSaving(true),
                onFinish: () => setSaving(false),
                // A save sent back (too many tries) also ends here, with its own
                // toast: then the server still has the old answers, so the sheet
                // stays open with the family's picks. It closes once they are saved.
                onSuccess: (page) => {
                    const own = page.props.own as OwnAttendance | null;
                    const now = own?.going[event.id] ?? [];
                    const sameAs = (ids: number[]) =>
                        now.length === ids.length &&
                        now.every((id) => ids.includes(id));

                    if (!sameAs(checked)) {
                        return;
                    }

                    if (!sameAs(goingIds)) {
                        toast.success(`Saved for ${place}`);
                    }

                    setOpen(false);
                },
                onError: (errors) =>
                    toast.error(errors.event ?? 'Could not save. Try again.'),
            },
        );
    }

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={openChange}
            trigger={trigger}
            title={`Going to ${event.shopName}?`}
            description={formatDay(event.startsAt)}
            footer={
                <>
                    <Button variant="outline" asChild>
                        <Link href={create.url()}>Add a trainer</Link>
                    </Button>
                    <Button onClick={save} disabled={saving}>
                        Save · {checked.length} going
                    </Button>
                </>
            }
        >
            <ul className="space-y-2">
                {trainers.map((trainer) => {
                    const isPicked = checked.includes(trainer.id);

                    return (
                        <li key={trainer.id}>
                            <button
                                type="button"
                                aria-pressed={isPicked}
                                disabled={saving}
                                onClick={() => toggle(trainer.id)}
                                className={cn(
                                    rowClasses,
                                    isPicked && rowPickedClasses,
                                )}
                            >
                                <span
                                    className={cn(
                                        'text-base',
                                        isPicked && 'font-bold',
                                    )}
                                >
                                    {trainer.name}
                                </span>
                                {/* Seen, not read: aria-pressed already tells screen readers. */}
                                <span
                                    aria-hidden="true"
                                    className={cn(
                                        'text-sm whitespace-nowrap',
                                        isPicked
                                            ? 'font-bold text-red-text'
                                            : 'text-muted',
                                    )}
                                >
                                    {isPicked ? 'Going ✓' : "I'm going"}
                                </span>
                            </button>
                        </li>
                    );
                })}
            </ul>
        </ResponsiveDialog>
    );
}
