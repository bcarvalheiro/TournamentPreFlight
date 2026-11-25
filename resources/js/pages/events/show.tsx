import { Head } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import type { EventPage } from '@/types';

type Props = {
    event: EventPage;
};

/** The event page (M09). Slice 1 shows the title only; the layout comes in Slice 3a. */
export default function EventShow({ event }: Props) {
    return (
        <>
            <Head title={event.title} />
            <h1 className="text-2xl font-bold">{event.title}</h1>
        </>
    );
}

EventShow.layout = [PublicLayout, {}];
