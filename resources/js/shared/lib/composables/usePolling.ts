// Generic polling: calls `task` every `interval` ms until `shouldContinue` returns false.
// - Only one loop at a time (start() stops the previous one).
// - On stop() or scope disposal (component unmount, effectScope.stop) the timer is cleared and
//   the in-flight fetch is aborted through an AbortController.
// - A late response (after stop) is ignored.
import { getCurrentScope, onScopeDispose, readonly, ref } from 'vue';
import { isAbortError } from '@/shared/api';

export type PollingOptions<T> = {
    interval: number;
    shouldContinue: (result: T) => boolean;
    onResult: (result: T) => void;
    onError?: (error: unknown) => void;
    /** true — first call right away, false — after `interval` */
    immediate?: boolean;
};

export function usePolling<T>(
    task: (signal: AbortSignal) => Promise<T>,
    options: PollingOptions<T>,
) {
    const active = ref(false);
    let timer: ReturnType<typeof setTimeout> | null = null;
    let controller: AbortController | null = null;
    let generation = 0;

    function stop(): void {
        generation++;
        active.value = false;
        if (timer !== null) clearTimeout(timer);
        timer = null;
        controller?.abort();
        controller = null;
    }

    async function run(myGeneration: number): Promise<void> {
        controller = new AbortController();
        try {
            const result = await task(controller.signal);
            if (myGeneration !== generation) return; // response from a stopped loop
            options.onResult(result);
            if (options.shouldContinue(result)) {
                timer = setTimeout(
                    () => void run(myGeneration),
                    options.interval,
                );
            } else {
                active.value = false;
            }
        } catch (error) {
            if (myGeneration !== generation || isAbortError(error)) return;
            active.value = false;
            options.onError?.(error);
        }
    }

    function start(): void {
        stop();
        const myGeneration = ++generation;
        active.value = true;
        if (options.immediate) {
            void run(myGeneration);
        } else {
            timer = setTimeout(() => void run(myGeneration), options.interval);
        }
    }

    if (getCurrentScope()) onScopeDispose(stop);

    return { start, stop, active: readonly(active) };
}
