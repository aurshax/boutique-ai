// Try-on formasining holati va undan hosil bo'ladigan qiymatlar (tanlangan look, shim tkanlari, narx).
// Hisob-kitob entities/catalog da (pure, test qilingan) — bu yerda faqat Vue reaktivligi.
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

    // Uslub almashsa — uning birinchi look'i; look almashsa — narx qo'shmaydigan tkan.
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
        // holat (v-model uchun yoziladigan ref'lar)
        photo,
        heightCm,
        waistCm,
        size,
        styleId,
        lookId,
        fabricId,
        quality,
        consent,
        // hosil qilinganlar
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
