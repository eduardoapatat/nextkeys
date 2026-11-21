/**
 * The deal of the day is picked, not discounted: a real product at its real
 * price, rotating with the date in Santiago. The countdown runs to the next
 * midnight there.
 */
const timeZone = 'America/Santiago';

function santiagoParts(now: Date): Record<string, number> {
    const parts = new Intl.DateTimeFormat('en-CA', {
        timeZone,
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        second: 'numeric',
        hourCycle: 'h23',
    }).formatToParts(now);

    return Object.fromEntries(
        parts
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, Number(part.value)]),
    );
}

/** Days since the epoch, counted in Santiago's calendar. */
export function santiagoDay(now: Date): number {
    const { year, month, day } = santiagoParts(now);

    return Math.floor(Date.UTC(year!, month! - 1, day!) / 86_400_000);
}

export function dealIndex(now: Date, count: number): number {
    return count === 0 ? 0 : santiagoDay(now) % count;
}

/** Seconds left until midnight in Santiago. */
export function secondsToSantiagoMidnight(now: Date): number {
    const { hour, minute, second } = santiagoParts(now);

    return 86_400 - (hour! * 3600 + minute! * 60 + second!);
}

export function formatCountdown(seconds: number): string {
    const pad = (value: number) => String(value).padStart(2, '0');

    return [
        Math.floor(seconds / 3600),
        Math.floor((seconds % 3600) / 60),
        seconds % 60,
    ]
        .map(pad)
        .join(':');
}
