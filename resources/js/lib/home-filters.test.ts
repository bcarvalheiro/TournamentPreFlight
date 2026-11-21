import { describe, expect, it } from 'vite-plus/test';
import { isUsableDay } from '@/lib/home-filters';

/*
 * A typed date is applied only once it is a whole day of `min` or later.
 * Chrome reports every digit while a year is typed ("0002-12-05", "0020-12-05"…);
 * applying those would send past days the server drops, emptying the box.
 */
describe('isUsableDay', () => {
    it.each([
        ['2026-12-24', true],
        ['2026-12-05', true],
        ['2026-12-04', false],
        ['0002-12-24', false],
        ['', false],
    ])('"%s" with min 2026-12-05 is %s', (value, usable) => {
        expect(isUsableDay(value, '2026-12-05')).toBe(usable);
    });
});
