// File → blob: URL (image preview). The old URL is released (URL.revokeObjectURL) when the file changes
// or the scope is disposed — otherwise it would leak browser memory.
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
