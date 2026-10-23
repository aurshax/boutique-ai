// Seconds since the component mounted (for the waiting timer).
import { getCurrentScope, onScopeDispose, readonly, ref } from 'vue';

export function useElapsedSeconds() {
    const seconds = ref(0);
    const timer = setInterval(() => seconds.value++, 1000);

    if (getCurrentScope()) onScopeDispose(() => clearInterval(timer));

    return readonly(seconds);
}
