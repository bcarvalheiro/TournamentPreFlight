/**
 * Date and time formatting for the UI.
 *
 * CLAUDE.md rule 4: times are stored in UTC and shown in Europe/Lisbon.
 * The browser's Intl API knows Lisbon's DST rules, so no date library is needed.
 */

export const DISPLAY_TIME_ZONE = 'Europe/Lisbon';

/** A UTC instant: an ISO 8601 string from the server (e.g. "2025-11-15T10:00:00Z") or a Date. */
export type Instant = string | Date;

const dayParts = new Intl.DateTimeFormat('en-GB', {
    timeZone: DISPLAY_TIME_ZONE,
    weekday: 'short',
    day: 'numeric',
    month: 'short',
});

const timeFormat = new Intl.DateTimeFormat('en-GB', {
    timeZone: DISPLAY_TIME_ZONE,
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

const calendarDateParts = new Intl.DateTimeFormat('en-GB', {
    timeZone: DISPLAY_TIME_ZONE,
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
});

const MS_PER_DAY = 86_400_000;

function toDate(instant: Instant): Date {
    return instant instanceof Date ? instant : new Date(instant);
}

function part(parts: Intl.DateTimeFormatPart[], type: string): string {
    return parts.find((p) => p.type === type)?.value ?? '';
}

/** "Sat 15 Nov": the Lisbon calendar day of the instant. */
export function formatDay(instant: Instant): string {
    const parts = dayParts.formatToParts(toDate(instant));

    return `${part(parts, 'weekday')} ${part(parts, 'day')} ${part(parts, 'month')}`;
}

/** "10:00": the Lisbon wall-clock time of the instant, 24-hour. */
export function formatTime(instant: Instant): string {
    return timeFormat.format(toDate(instant));
}

/** Days since 1970-01-01 of the Lisbon calendar date, so two dates can be subtracted. */
function lisbonDayNumber(instant: Instant): number {
    const parts = calendarDateParts.formatToParts(toDate(instant));
    const utcMidnight = Date.UTC(
        Number(part(parts, 'year')),
        Number(part(parts, 'month')) - 1,
        Number(part(parts, 'day')),
    );

    return utcMidnight / MS_PER_DAY;
}

/**
 * "Today", "Tomorrow", "In 3 days", "Next week" or "In 2 weeks", counted in
 * Lisbon calendar days from `now`.
 */
export function formatRelativeDay(
    instant: Instant,
    now: Instant = new Date(),
): string {
    const days = lisbonDayNumber(instant) - lisbonDayNumber(now);

    if (days <= 0) {
        return 'Today';
    }

    if (days === 1) {
        return 'Tomorrow';
    }

    if (days < 7) {
        return `In ${days} days`;
    }

    if (days < 14) {
        return 'Next week';
    }

    return `In ${Math.floor(days / 7)} weeks`;
}
