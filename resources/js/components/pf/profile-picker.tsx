import { useState } from 'react';
import type { ReactElement, ReactNode } from 'react';
import { ResponsiveDialog } from '@/components/pf/responsive-dialog';
import { TrainerToggleRows } from '@/components/pf/trainer-toggle-rows';
import { Button } from '@/components/ui/button';
import { formatDay } from '@/lib/time';
import type { EventCard, OwnAttendance } from '@/types';

type ProfilePickerProps = {
    event: EventCard;
    /** The account's trainers, oldest first. */
    trainers: OwnAttendance['trainers'];
    /** Ids of the trainers going to this event now. */
    goingIds: number[];
    /** The button that opens the picker; none when the page opens it (`defaultOpen`). */
    trigger?: ReactElement;
    /** Open on first render: the page resumes a pending "Going?" (M08). */
    defaultOpen?: boolean;
};

/**
 * "Who is going?" for a family account on the home page (design.md,
 * ProfilePicker): the event page's trainer rows in a sheet. Each tap saves
 * that trainer at once (M09 Slice 5b; there is no Save button), the home
 * page reloads its lists and `own`, and the rows follow; Done closes it.
 */
export function ProfilePicker({
    event,
    trainers,
    goingIds,
    trigger,
    defaultOpen = false,
}: ProfilePickerProps): ReactNode {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <ResponsiveDialog
            open={open}
            onOpenChange={setOpen}
            trigger={trigger}
            title={`Going to ${event.shopName}?`}
            description={formatDay(event.startsAt)}
            footer={<Button onClick={() => setOpen(false)}>Done</Button>}
        >
            <TrainerToggleRows
                eventId={event.id}
                place={`${event.shopName}, ${formatDay(event.startsAt)}`}
                trainers={trainers}
                goingIds={goingIds}
                reload={['events', 'upNext', 'own']}
            />
        </ResponsiveDialog>
    );
}
