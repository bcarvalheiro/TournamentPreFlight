import { Head } from '@inertiajs/react';
import { EventCounts } from '@/components/pf/event-counts';
import { EventDetails } from '@/components/pf/event-details';
import { EventHeader } from '@/components/pf/event-header';
import { EventShop } from '@/components/pf/event-shop';
import PublicLayout from '@/layouts/public-layout';
import type { EventPage } from '@/types';

type Props = {
    event: EventPage;
};

/**
 * The event page (M09, design.md "Event page"). The header lives in the dark
 * band; below it, three columns from 1024px: who's heading there, the shop
 * (Slice 3b) and the details (Slice 3c), stacked on phones.
 */
export default function EventShow({ event }: Props) {
    return (
        <>
            <Head title={event.title} />
            <div className="grid gap-6 lg:grid-cols-3">
                <EventCounts
                    goingCount={event.goingCount}
                    confirmedCount={event.confirmedCount}
                />
                <EventShop shop={event} />
                <EventDetails event={event} />
            </div>
        </>
    );
}

// A layout callback (Inertia "Layouts › Callback Props"): it receives the page
// props, so the band's hero can show this event.
EventShow.layout = (props: Props) => [
    PublicLayout,
    { hero: <EventHeader event={props.event} /> },
];
