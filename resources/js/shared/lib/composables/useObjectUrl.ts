// File → blob: URL (rasm preview). Fayl almashganda yoki scope yopilganda eski URL bo'shatiladi
// (URL.revokeObjectURL) — aks holda brauzer xotirasida qolib ketadi.
import { getCurrentScope, onScopeDispose, readonly, ref, watch } from 'vue';
import type { Ref } from 'vue';

export function useObjectUrl(file: Ref<File | null>) {
    const url = ref<string | null>(null);

    function release(): void {
        if (url.value) URL.revokeObjectURL(url.value);
        url.value = null;
    }

    watch(
        file,
        (next) => {
            release();
            if (next) url.value = URL.createObjectURL(next);
        },
        { immediate: true },
    );

    if (getCurrentScope()) onScopeDispose(release);

    return readonly(url);
}
