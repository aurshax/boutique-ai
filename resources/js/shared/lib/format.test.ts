import { describe, expect, it } from 'vite-plus/test';
import {
    formatDuration,
    formatShortSurcharge,
    formatUsd,
    formatUsdRange,
    formatUzs,
} from './format';

describe('formatUzs', () => {
    it('groups thousands with spaces', () => {
        expect(formatUzs(4_357_000)).toBe('4 357 000');
        expect(formatUzs(690_000)).toBe('690 000');
        expect(formatUzs(999)).toBe('999');
        expect(formatUzs(0)).toBe('0');
    });

    it('rounds fractional input', () => {
        expect(formatUzs(1234.6)).toBe('1 235');
    });
});

describe('formatShortSurcharge', () => {
    it('shows thousands as k and zero as 0', () => {
        expect(formatShortSurcharge(130_000)).toBe('+130k');
        expect(formatShortSurcharge(0)).toBe('0');
        expect(formatShortSurcharge(500)).toBe('+500');
    });
});

describe('formatUsd / formatUsdRange', () => {
    it('formats dollars', () => {
        expect(formatUsd(0.05)).toBe('$0.05');
        expect(formatUsd(0.0512, 4)).toBe('$0.0512');
        expect(formatUsdRange(0.05, 0.09)).toBe('$0.05–0.09');
    });
});

describe('formatDuration', () => {
    it('formats seconds as m:ss', () => {
        expect(formatDuration(0)).toBe('0:00');
        expect(formatDuration(18)).toBe('0:18');
        expect(formatDuration(75)).toBe('1:15');
        expect(formatDuration(-3)).toBe('0:00');
    });
});
