// Loads an image before it is shown, so the page reveals a finished picture instead of one that paints line by line.
// Like the WordPress plugin's preload(): an error also counts as "ready" — the <img> then shows its alt text
// instead of the page waiting forever. A newer URL cancels the older one.
import { getCurrentScope, onScopeDispose, readonly, ref, watch } from 'vue';
import type { Ref } from 'vue';

/** width / height, or null when the image has no usable size (not loaded, broken, SVG without dimensions). */
export function imageRatio(width: number, height: number): number | null {
    return width > 0 && height > 0 ? width / height : null;
}

export function useImagePreload(url: Ref<string | null | undefined>) {
    const ready = ref(false);
    const ratio = ref<number | null>(null);
    let generation = 0;

    watch(
        url,
        (next) => {
            const current = ++generation;
            ready.value = false;
            ratio.value = null;
            if (!next) return;

            const image = new Image();
            const settle = (): void => {
                if (current !== generation) return;
                ratio.value = imageRatio(
                    image.naturalWidth,
                    image.naturalHeight,
                );
                ready.value = true;
            };
            image.onload = settle;
            image.onerror = settle;
            image.src = next;
        },
        { immediate: true },
    );

    if (getCurrentScope()) onScopeDispose(() => generation++);

    return { ready: readonly(ready), ratio: readonly(ratio) };
}
