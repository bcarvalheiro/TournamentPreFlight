import type { ReactNode } from 'react';
import { CoinFlip } from '@/components/pf/coin-flip';
import PublicLayout from '@/layouts/public-layout';
import type { EventCard } from '@/types';

type HomeProps = {
    events: EventCard[];
};

/** The band's hero (design.md, Band): headline, question, what the app is, coin flip. */
function Hero(): ReactNode {
    return (
        <div className="grid gap-[22px] pt-3.5 pb-[26px] min-[900px]:grid-cols-[5fr_7fr] min-[900px]:items-center min-[900px]:pt-[30px] min-[900px]:pb-10">
            <div>
                <h1 className="font-display text-[clamp(2.4rem,10vw,4.2rem)] leading-[0.92] font-extrabold tracking-[0.01em] text-white uppercase">
                    Pre-Flight
                </h1>
                <p className="mt-2.5 text-[1.15rem] text-white">
                    Should I go or should I{' '}
                    <b className="text-red">stay at home?</b>
                </p>
                <p className="mt-2.5 max-w-[44ch] text-[0.9rem] text-[#A9B4BF]">
                    See how many players are heading to each Pokémon TCG
                    tournament in Portugal before you sleeve up.
                </p>
                <CoinFlip />
            </div>
        </div>
    );
}

/** Stub list until the agenda and pairings sheet land (M05 slices 10–14). */
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

Home.layout = [PublicLayout, { hero: <Hero /> }];
