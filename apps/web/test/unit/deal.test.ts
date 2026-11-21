import { describe, expect, it } from 'vitest';
import {
    dealIndex,
    formatCountdown,
    secondsToSantiagoMidnight,
} from '../../app/utils/deal';

describe('deal of the day', () => {
    it('counts down to midnight in Santiago, not UTC', () => {
        // 2026-11-21 23:00 in Santiago (UTC-3 in summer) is 02:00 UTC.
        expect(
            secondsToSantiagoMidnight(new Date('2026-11-22T02:00:00Z')),
        ).toBe(3600);
    });

    it('keeps the same product all day in Santiago and rotates at its midnight', () => {
        const morning = dealIndex(new Date('2026-11-21T12:00:00Z'), 10);
        const lateNight = dealIndex(new Date('2026-11-22T02:59:59Z'), 10);
        const nextDay = dealIndex(new Date('2026-11-22T03:00:00Z'), 10);

        expect(lateNight).toBe(morning);
        expect(nextDay).toBe((morning + 1) % 10);
    });

    it('formats the countdown as hours, minutes and seconds', () => {
        expect(formatCountdown(3 * 3600 + 7 * 60 + 5)).toBe('03:07:05');
    });
});
