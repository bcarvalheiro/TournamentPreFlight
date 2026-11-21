import { describe, expect, it } from 'vite-plus/test';
import { nextCoinRotation } from '@/lib/coin-flip';

/*
 * The coin spins four full turns, then lands on the face it announces:
 * an even number of half turns shows GO (heads), an odd number STAY (tails).
 */
describe('nextCoinRotation', () => {
    it.each([
        ['GO', 0, true, 1440],
        ['GO', 0, false, 1620],
        ['STAY', 1620, true, 3240],
        ['STAY', 1620, false, 3060],
    ])('from %s at %i°, heads=%s lands at %i°', (_face, from, heads, to) => {
        expect(nextCoinRotation(from, heads)).toBe(to);
    });
});
