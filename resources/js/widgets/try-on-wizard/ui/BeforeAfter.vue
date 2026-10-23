<script setup lang="ts">
// Before/after slider. The result unrolls from the top "like cloth" (clip-path).
// The frame uses the result's own aspect ratio (no cropping) and fits the viewport height, so a full-length
// photo shows the feet too. Input and output share one aspect ratio (backend OutputSize), so the slider lines up.
import { motion } from 'motion-v';
import { computed, ref, useId } from 'vue';
import { useI18n } from '@/shared/i18n';

defineProps<{ before: string | null; after: string }>();
const { t } = useI18n();
const split = ref(55);
const sliderId = useId();

/** 2:3 (a full-length frame) until the result loads, then the image's real aspect ratio. */
const ratio = ref(2 / 3);
function onAfterLoad(event: Event): void {
    const img = event.target as HTMLImageElement;
    if (img.naturalWidth > 0 && img.naturalHeight > 0)
        ratio.value = img.naturalWidth / img.naturalHeight;
}
const frameStyle = computed(() => ({
    aspectRatio: String(ratio.value),
    width: `min(100%, calc(82svh * ${ratio.value}))`,
}));
</script>

<template>
    <div class="flex flex-col gap-3.5">
        <div
            class="relative mx-auto overflow-hidden rounded-3xl bg-[#DDE1E7]"
            :style="frameStyle"
        >
            <img
                v-if="before"
                :src="before"
                :alt="t('tryOn.compare.beforeAlt')"
                class="absolute inset-0 h-full w-full object-cover"
            />
            <motion.div
                class="absolute inset-0"
                :style="{ clipPath: `inset(0 ${100 - split}% 0 0)` }"
                :initial="{ opacity: 0, y: -24 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.8, ease: [0.2, 0.8, 0.2, 1] }"
            >
                <img
                    :src="after"
                    :alt="t('tryOn.compare.afterAlt')"
                    class="h-full w-full object-cover"
                    @load="onAfterLoad"
                />
            </motion.div>
            <div
                v-if="before"
                aria-hidden="true"
                class="absolute inset-y-0 w-0.5 -translate-x-1/2 bg-chalk"
                :style="{ left: `${split}%` }"
            />
            <span
                class="absolute top-4 left-4 rounded-full bg-ink/85 px-3 py-2 text-[13px] font-semibold text-chalk"
                >{{ t('tryOn.compare.after') }}</span
            >
            <span
                v-if="before"
                class="absolute top-4 right-4 rounded-full bg-white/90 px-3 py-2 text-[13px] font-semibold text-ink"
                >{{ t('tryOn.compare.before') }}</span
            >
        </div>
        <div v-if="before" class="flex items-center gap-3.5">
            <label
                :for="sliderId"
                class="text-sm whitespace-nowrap text-slate"
                >{{ t('tryOn.compare.slider') }}</label
            >
            <input
                :id="sliderId"
                v-model.number="split"
                type="range"
                min="0"
                max="100"
                class="flex-1 accent-chalk-deep"
            />
        </div>
        <p class="text-[13px] text-slate">
            {{ t('tryOn.compare.disclaimer') }}
        </p>
    </div>
</template>
