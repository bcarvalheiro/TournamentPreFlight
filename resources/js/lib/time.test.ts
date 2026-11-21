import { describe, expect, it } from 'vite-plus/test';
import {
    formatDay,
    formatRelativeDay,
    formatTime,
    groupByLisbonDay,
    lisbonDate,
} from '@/lib/time';

/*
 * CLAUDE.md rule 4: times are stored in UTC and shown in Europe/Lisbon.
 * Lisbon is UTC+0 in winter and UTC+1 in summer (DST), so the same UTC hour
 * shows a different Lisbon hour depending on the date.
 */
describe('formatDay and formatTime', () => {
    it('shows a winter instant as "Sat 15 Nov" at "10:00"', () => {
        const instant = '2025-11-15T10:00:00Z';

        expect(formatDay(instant)).toBe('Sat 15 Nov');
        expect(formatTime(instant)).toBe('10:00');
    });

    it('adds the summer hour in July', () => {
        const instant = '2026-07-11T09:00:00Z';

        expect(formatDay(instant)).toBe('Sat 11 Jul');
        expect(formatTime(instant)).toBe('10:00');
    });

    it('keeps the UTC hour in December', () => {
        const instant = '2026-12-12T10:00:00Z';

        expect(formatDay(instant)).toBe('Sat 12 Dec');
        expect(formatTime(instant)).toBe('10:00');
    });

    it('uses the Lisbon calendar day, not the UTC day', () => {
        const instant = '2026-07-10T23:30:00Z';

        expect(formatDay(instant)).toBe('Sat 11 Jul');
        expect(formatTime(instant)).toBe('00:30');
    });
});

describe('formatRelativeDay', () => {
    const now = '2026-11-17T12:00:00Z';

    it.each([
        ['2026-11-17T18:00:00Z', 'Today'],
        ['2026-11-18T10:00:00Z', 'Tomorrow'],
        ['2026-11-19T10:00:00Z', 'In 2 days'],
        ['2026-11-20T10:00:00Z', 'In 3 days'],
        ['2026-11-23T10:00:00Z', 'In 6 days'],
        ['2026-11-24T10:00:00Z', 'Next week'],
        ['2026-11-30T10:00:00Z', 'Next week'],
        ['2026-12-01T10:00:00Z', 'In 2 weeks'],
        ['2026-12-15T10:00:00Z', 'In 4 weeks'],
    ])('labels %s as "%s"', (instant, label) => {
        expect(formatRelativeDay(instant, now)).toBe(label);
    });

    it('counts Lisbon calendar days late at night in summer', () => {
        const lateFridayInLisbon = '2026-07-10T22:30:00Z';
        const justAfterMidnightInLisbon = '2026-07-10T23:30:00Z';

        expect(
            formatRelativeDay(justAfterMidnightInLisbon, lateFridayInLisbon),
        ).toBe('Tomorrow');
    });
});

describe('groupByLisbonDay', () => {
    it('puts events of the same Lisbon day together, in order', () => {
        const groups = groupByLisbonDay([
            { id: 1, startsAt: '2026-12-05T10:00:00Z' },
            { id: 2, startsAt: '2026-12-05T18:30:00Z' },
            { id: 3, startsAt: '2026-12-06T10:00:00Z' },
        ]);

        expect(groups.map((group) => group.events.map((e) => e.id))).toEqual([
            [1, 2],
            [3],
        ]);
    });

    it('uses the Lisbon day, not the UTC day, in summer', () => {
        const groups = groupByLisbonDay([
            { id: 1, startsAt: '2027-07-10T20:00:00Z' }, // Sat 21:00 in Lisbon
            { id: 2, startsAt: '2027-07-10T23:30:00Z' }, // Sun 00:30 in Lisbon
        ]);

        expect(groups.map((group) => formatDay(group.day))).toEqual([
            'Sat 10 Jul',
            'Sun 11 Jul',
        ]);
    });
});

describe('lisbonDate', () => {
    it('gives the Lisbon calendar day as "YYYY-MM-DD", a day ahead of UTC late on a summer night', () => {
        expect(lisbonDate('2026-12-05T23:30:00Z')).toBe('2026-12-05');
        expect(lisbonDate('2027-07-10T23:30:00Z')).toBe('2027-07-11');
    });
});
