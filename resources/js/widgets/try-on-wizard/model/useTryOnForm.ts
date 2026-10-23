// Try-on form state and the values derived from it (selected look, available fabrics, price).
// The maths lives in entities/catalog (pure, unit tested) — this file is only Vue reactivity.
import { computed, ref, watch } from 'vue';
import {
    defaultFabricId,
    fabricsOf,
    findFabric,
    findLook,
    findStyleOfLook,
    firstLookId,
    lookTotal,
} from '@/entities/catalog';
import type { Style } from '@/entities/catalog';
import type { Quality } from '@/entities/try-on-request';
import { useObjectUrl } from '@/shared/lib/composables/useObjectUrl';

export const DEFAULT_HEIGHT_CM = 178;

export function useTryOnForm(
    styles: Style[],
    defaults: { styleIndex?: number; size?: string | null } = {},
) {
    const initialStyle = styles[defaults.styleIndex ?? 0] ?? styles[0];

    const photo = ref<File | null>(null);
    const photoUrl = useObjectUrl(photo);
    const heightCm = ref(DEFAULT_HEIGHT_CM);
    const waistCm = ref<number | null>(null);
    const size = ref<string | null>(defaults.size ?? null);
    const styleId = ref<number>(initialStyle?.id ?? 0);
    const lookId = ref<number>(initialStyle?.looks[0]?.id ?? 0);
    const fabricId = ref<number | null>(null);
    const quality = ref<Quality>('medium');
    const consent = ref(false);

    const look = computed(() => findLook(styles, lookId.value));
    const style = computed(() => findStyleOfLook(styles, lookId.value));
    const fabrics = computed(() => fabricsOf(look.value));
    const fabric = computed(() => findFabric(look.value, fabricId.value));
    const total = computed(() => lookTotal(look.value, fabricId.value));

    // Style changes → its first look; look changes → the fabric without a surcharge.
    watch(styleId, (id) => {
        if (style.value?.id !== id)
            lookId.value = firstLookId(styles, id) ?? lookId.value;
    });
    watch(
        lookId,
        () => {
            fabricId.value = defaultFabricId(look.value);
        },
        { immediate: true },
    );

    function setPhoto(file: File | null): void {
        photo.value = file;
    }

    return {
        // state (writable refs for v-model)
        photo,
        heightCm,
        waistCm,
        size,
        styleId,
        lookId,
        fabricId,
        quality,
        consent,
        // derived
        photoUrl,
        look,
        style,
        fabrics,
        fabric,
        total,
        setPhoto,
    };
}

export type TryOnForm = ReturnType<typeof useTryOnForm>;
