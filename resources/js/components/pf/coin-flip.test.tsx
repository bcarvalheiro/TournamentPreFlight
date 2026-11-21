import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vite-plus/test';
import { CoinFlip } from '@/components/pf/coin-flip';

describe('CoinFlip', () => {
    const markup = renderToStaticMarkup(<CoinFlip />);

    it('is a labelled button with a GO and a STAY face', () => {
        expect(markup).toContain(
            'aria-label="Flip a coin: heads go, tails stay"',
        );
        expect(markup).toContain('>GO<');
        expect(markup).toContain('>STAY<');
    });

    it('announces the result politely and hides the decoration', () => {
        // React writes the apostrophe of "can't" as &#x27;.
        expect(markup).toContain('aria-live="polite">Still can&#x27;t decide?');
        expect(markup).toMatch(/aria-hidden="true"[^>]*><span[^>]*>Prizes</);
    });
});
