import { describe, expect, it } from 'vite-plus/test';
import { transition } from './flow';

describe('flow.transition', () => {
    it('goes setup → waiting → result on the happy path', () => {
        const waiting = transition('setup', { type: 'submitted' });
        expect(waiting).toBe('waiting');
        expect(transition(waiting, { type: 'status', status: 'running' })).toBe(
            'waiting',
        );
        expect(transition(waiting, { type: 'status', status: 'done' })).toBe(
            'result',
        );
    });

    it('returns to setup on failure or cancel', () => {
        expect(
            transition('waiting', { type: 'status', status: 'failed' }),
        ).toBe('setup');
        expect(
            transition('waiting', { type: 'status', status: 'cancelled' }),
        ).toBe('setup');
    });

    it('ignores a late "done" when user already went back to setup', () => {
        expect(transition('setup', { type: 'status', status: 'done' })).toBe(
            'setup',
        );
    });

    it('edit leaves result for setup; reset always goes to setup', () => {
        expect(transition('result', { type: 'edit' })).toBe('setup');
        expect(transition('waiting', { type: 'edit' })).toBe('waiting');
        expect(transition('result', { type: 'reset' })).toBe('setup');
    });
});
