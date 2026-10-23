// Umumiy polling: `task` ni har `interval` ms da chaqiradi, `shouldContinue` false bo'lguncha.
// - Bir vaqtda faqat bitta tsikl (start() eskisini to'xtatadi).
// - stop() yoki scope yopilganda (komponent unmount, effectScope.stop) — taymer tozalanadi va
//   uchib ketayotgan fetch AbortController orqali bekor qilinadi.
// - Kechikib kelgan javob (stop'dan keyin) e'tiborsiz qoladi.
import { getCurrentScope, onScopeDispose, readonly, ref } from 'vue';
import { isAbortError } from '@/shared/api';

export type PollingOptions<T> = {
    interval: number;
    shouldContinue: (result: T) => boolean;
    onResult: (result: T) => void;
    onError?: (error: unknown) => void;
    /** true — birinchi chaqiruv darhol, false — `interval` dan keyin */
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
            if (myGeneration !== generation) return; // to'xtatilgan tsiklning javobi
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
