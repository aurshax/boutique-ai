import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';
import { effectScope } from 'vue';
import { usePolling } from './usePolling';

describe('usePolling', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('polls until shouldContinue returns false', async () => {
        const statuses = ['queued', 'running', 'done'];
        const task = vi.fn(async () => statuses.shift() ?? 'done');
        const seen: string[] = [];

        const polling = usePolling(task, {
            interval: 2000,
            shouldContinue: (s) => s !== 'done',
            onResult: (s) => seen.push(s),
        });
        polling.start();

        await vi.advanceTimersByTimeAsync(2000 * 5);

        expect(seen).toEqual(['queued', 'running', 'done']);
        expect(task).toHaveBeenCalledTimes(3);
        expect(polling.active.value).toBe(false);
    });

    it('start() restarts: only one loop runs at a time', async () => {
        const task = vi.fn(async () => 'running');
        const polling = usePolling(task, {
            interval: 1000,
            shouldContinue: () => true,
            onResult: () => {},
        });

        polling.start();
        polling.start(); // masalan: qayta submit
        await vi.advanceTimersByTimeAsync(3000);

        expect(task).toHaveBeenCalledTimes(3); // 2 ta parallel tsikl bo'lsa 6 bo'lardi
        polling.stop();
    });

    it('stops and aborts in-flight request when the scope is disposed (unmount)', async () => {
        let receivedSignal: AbortSignal | null = null;
        const task = vi.fn((signal: AbortSignal) => {
            receivedSignal = signal;
            return new Promise<string>(() => {}); // hech qachon tugamaydi
        });

        const scope = effectScope();
        scope.run(() => {
            usePolling(task, {
                interval: 1000,
                shouldContinue: () => true,
                onResult: () => {},
                immediate: true,
            }).start();
        });
        scope.stop();

        expect(receivedSignal!.aborted).toBe(true);
        await vi.advanceTimersByTimeAsync(5000);
        expect(task).toHaveBeenCalledTimes(1);
    });

    it('ignores a late result after stop()', async () => {
        let resolve: (v: string) => void = () => {};
        const onResult = vi.fn();
        const polling = usePolling(
            () =>
                new Promise<string>((r) => {
                    resolve = r;
                }),
            {
                interval: 1000,
                shouldContinue: () => true,
                onResult,
                immediate: true,
            },
        );

        polling.start();
        polling.stop();
        resolve('done');
        await vi.advanceTimersByTimeAsync(0);

        expect(onResult).not.toHaveBeenCalled();
    });

    it('reports errors and stops', async () => {
        const onError = vi.fn();
        const polling = usePolling(
            async () => {
                throw new Error('network');
            },
            {
                interval: 1000,
                shouldContinue: () => true,
                onResult: () => {},
                onError,
                immediate: true,
            },
        );

        polling.start();
        await vi.advanceTimersByTimeAsync(0);

        expect(onError).toHaveBeenCalledOnce();
        expect(polling.active.value).toBe(false);
    });
});
