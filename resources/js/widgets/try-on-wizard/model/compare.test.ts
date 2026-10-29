import { describe, expect, it } from 'vite-plus/test';
import {
    clampPercent,
    COMPARE_START,
    compareFrameStyle,
    FALLBACK_RATIO,
} from './compare';

describe('clampPercent', () => {
    it('keeps values between 0 and 100', () => {
        expect(clampPercent('42')).toBe(42);
        expect(clampPercent(-5)).toBe(0);
        expect(clampPercent(140)).toBe(100);
    });

    it('treats anything that is not a number as 0', () => {
        expect(clampPercent('abc')).toBe(0);
        expect(clampPercent(undefined)).toBe(0);
        expect(clampPercent(Number.NaN)).toBe(0);
    });
});

describe('compareFrameStyle', () => {
    it('uses the 2:3 fallback until the real ratio is known', () => {
        const style = compareFrameStyle(null, COMPARE_START);
        expect(style.aspectRatio).toBe(String(FALLBACK_RATIO));
        expect(style['--position']).toBe('50%');
    });

    it('fits the frame to the viewport height with the real ratio', () => {
        const style = compareFrameStyle(0.75, 30);
        expect(style.aspectRatio).toBe('0.75');
        expect(style.width).toBe('min(100%, calc(82svh * 0.75))');
        expect(style['--position']).toBe('30%');
    });

    it('clamps the divider position', () => {
        expect(compareFrameStyle(1, 250)['--position']).toBe('100%');
    });
});
