import { useState } from 'react';
import type { ReactNode } from 'react';
import { nextCoinRotation } from '@/lib/coin-flip';

type Result = 'heads' | 'tails';

const face =
    'absolute inset-0 grid place-items-center rounded-full font-display text-base font-extrabold tracking-wide backface-hidden shadow-coin';

/** Decoration only: six prize-card spaces, one showing GO (design.md, Band). */
function PrizeZones(): ReactNode {
    return (
        <div aria-hidden="true" className="flex items-center gap-2">
            {/* Tailwind has no writing-mode utility, so it is written as [property:value]. */}
            <span className="rotate-180 font-mono text-2xs tracking-widest text-white/40 uppercase [writing-mode:vertical-rl]">
                Prizes
            </span>
            <div className="grid grid-cols-3 gap-1.25">
                {[0, 1, 2, 3, 4, 5].map((zone) => (
                    <span
                        key={zone}
                        className={
                            zone === 2
                                ? 'grid h-9 w-6.5 place-items-center rounded border-[1.5px] border-red font-mono text-[0.5rem] text-red'
                                : 'h-9 w-6.5 rounded border-[1.5px] border-dashed border-white/18'
                        }
                    >
                        {zone === 2 ? 'GO' : null}
                    </span>
                ))}
            </div>
        </div>
    );
}

/** "Still can't decide? Flip for it." A bit of fun in the hero (design.md, Band). */
export function CoinFlip(): ReactNode {
    const [rotation, setRotation] = useState(0);
    const [flipping, setFlipping] = useState<Result | null>(null);
    const [result, setResult] = useState<Result | null>(null);

    function flip(): void {
        const heads = Math.random() < 0.5;
        setRotation(nextCoinRotation(rotation, heads));
        setFlipping(heads ? 'heads' : 'tails');
        setResult(null);
    }

    // The message waits for the coin to stop; with reduced motion that is almost at once.
    function showResult(): void {
        setResult(flipping);
        setFlipping(null);
    }

    return (
        <div className="mt-4.5 flex flex-wrap items-center gap-3.5">
            <PrizeZones />
            <div className="flex min-w-0 flex-1 items-center gap-2.5 text-sm leading-snug text-board-muted">
                <button
                    type="button"
                    aria-label="Flip a coin: heads go, tails stay"
                    onClick={flip}
                    className="size-14.5 flex-none cursor-pointer rounded-full perspective-[400px]"
                >
                    <span
                        className="relative block size-full transition-transform duration-1100 ease-coin transform-3d"
                        style={{ transform: `rotateY(${rotation}deg)` }}
                        onTransitionEnd={showResult}
                    >
                        <span className={`${face} bg-red text-white`}>GO</span>
                        <span
                            className={`${face} rotate-y-180 bg-board-fg text-ink`}
                        >
                            STAY
                        </span>
                    </span>
                </button>
                <span aria-live="polite">
                    {flipping && 'Flipping…'}
                    {result === 'heads' && (
                        <>
                            <b className="text-white">Heads!</b> Pack your deck.
                            <br />
                            Pick an event below.
                        </>
                    )}
                    {result === 'tails' && (
                        <>
                            <b className="text-white">Tails.</b> Stay home and
                            <br />
                            test some new lists.
                        </>
                    )}
                    {!flipping && !result && (
                        <>
                            Still can&apos;t decide?
                            <br />
                            <b className="text-white">Flip for it.</b> Heads you
                            go.
                        </>
                    )}
                </span>
            </div>
        </div>
    );
}
