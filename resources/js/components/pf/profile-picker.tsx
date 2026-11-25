import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import type { ReactElement, ReactNode } from 'react';
import { toast } from 'sonner';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { formatDay } from '@/lib/time';
import { update } from '@/routes/events/attendance';
import { create } from '@/routes/profiles';
import type { EventCard, OwnAttendance } from '@/types';

type ProfilePickerProps = {
    event: EventCard;
    /** The account's trainers, oldest first. */
    trainers: OwnAttendance['trainers'];
    /** Ids of the trainers going to this event now. */
    goingIds: number[];
    /** The button that opens the picker. */
    trigger: ReactElement;
};

/**
 * "Who is going?" for a family account (design.md, ProfilePicker; M08): a
 * going checkbox per trainer, then Save sends every choice in one request.
 * The sheet opens with the saved answers each time.
 */
export function ProfilePicker({
    event,
    trainers,
    goingIds,
    trigger,
}: ProfilePickerProps): ReactNode {
    const [open, setOpen] = useState(false);
    const [checked, setChecked] = useState<number[]>(goingIds);
    const [saving, setSaving] = useState(false);
    const place = `${event.shopName}, ${formatDay(event.startsAt)}`;

    function openChange(isOpen: boolean): void {
        if (isOpen) {
            setChecked(goingIds);
        }

        setOpen(isOpen);
    }

    function toggle(trainerId: number, isChecked: boolean): void {
        setChecked((current) =>
            isChecked
                ? [...current, trainerId]
                : current.filter((id) => id !== trainerId),
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
                // Only when the server's answers really changed: a save sent back
                // (too many tries) also ends here, with its own toast.
                onSuccess: (page) => {
                    const own = page.props.own as OwnAttendance | null;
                    const now = own?.going[event.id] ?? [];
                    const changed =
                        now.length !== goingIds.length ||
                        now.some((id) => !goingIds.includes(id));

                    if (changed) {
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
                        Save
                    </Button>
                </>
            }
        >
            <ul className="divide-y divide-line">
                {trainers.map((trainer) => {
                    const id = `going-${event.id}-${trainer.id}`;

                    return (
                        <li
                            key={trainer.id}
                            className="flex min-h-10 items-center gap-3 py-1"
                        >
                            <Checkbox
                                id={id}
                                checked={checked.includes(trainer.id)}
                                onCheckedChange={(state) =>
                                    toggle(trainer.id, state === true)
                                }
                            />
                            <Label
                                htmlFor={id}
                                className="flex min-h-10 flex-1 items-center text-base"
                            >
                                {trainer.name}
                            </Label>
                        </li>
                    );
                })}
            </ul>
        </ResponsiveDialog>
    );
}
