import { describe, expect, it } from 'vite-plus/test';
import { isFinished, isPending } from './status';

describe('status', () => {
    it('isPending covers queued and running only', () => {
        expect(isPending('queued')).toBe(true);
        expect(isPending('running')).toBe(true);
        expect(isPending('done')).toBe(false);
        expect(isPending(undefined)).toBe(false);
    });

    it('isFinished covers terminal statuses', () => {
        expect(isFinished('done')).toBe(true);
        expect(isFinished('failed')).toBe(true);
        expect(isFinished('cancelled')).toBe(true);
        expect(isFinished('running')).toBe(false);
    });
});
