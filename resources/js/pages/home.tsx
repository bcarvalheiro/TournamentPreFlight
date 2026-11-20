import PublicLayout from '@/layouts/public-layout';
import type { EventCard } from '@/types';

type HomeProps = {
    events: EventCard[];
};

/** Stub until the agenda and pairings sheet land (M05 slices 9–14). */
export default function Home({ events }: HomeProps) {
    return (
        <ul className="space-y-1">
            {events.map((event) => (
                <li key={event.id}>
                    {event.title} · {event.goingCount}
                </li>
            ))}
        </ul>
    );
}

Home.layout = [PublicLayout];
