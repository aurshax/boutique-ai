<script setup lang="ts">
// Before/after comparison, aligned with the WordPress plugin: the result is the base image, the original photo
// lies on top and is clipped at the divider (before on the left, after on the right). A native range input
// covers the whole frame, so dragging, tapping, arrow keys, Home/End and screen readers all work without
// custom pointer code. The result is revealed only once it has loaded.
import { ChevronsLeftRight } from '@lucide/vue';
import { motion } from 'motion-v';
import { computed, ref, toRef } from 'vue';
import { useI18n } from '@/shared/i18n';
import { useImagePreload } from '@/shared/lib/composables/useImagePreload';
import {
    clampPercent,
    COMPARE_START,
    compareFrameStyle,
} from '../model/compare';

const props = defineProps<{ before: string | null; after: string }>();
const { t } = useI18n();

const position = ref(COMPARE_START);
const { ready, ratio } = useImagePreload(toRef(props, 'after'));
const frameStyle = computed(() =>
    compareFrameStyle(ratio.value, position.value),
);
const valueText = computed(() =>
    t('tryOn.compare.position', {
        before: position.value,
        after: 100 - position.value,
    }),
);

function onSlide(event: Event): void {
    position.value = clampPercent((event.target as HTMLInputElement).value);
}
</script>

<template>
    <div class="flex flex-col gap-3.5">
        <div
            class="group relative mx-auto overflow-hidden rounded-3xl bg-[#DDE1E7] select-none"
            :style="frameStyle"
            :aria-busy="!ready"
        >
            <div
                v-if="!ready"
                role="status"
                class="absolute inset-0 animate-pulse bg-[#CDD2DA]"
            >
                <span class="sr-only">{{ t('tryOn.compare.loading') }}</span>
            </div>

            <motion.div
                v-else
                class="absolute inset-0"
                :initial="{ opacity: 0, y: -24 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ duration: 0.8, ease: [0.2, 0.8, 0.2, 1] }"
            >
                <img
                    :src="after"
                    :alt="t('tryOn.compare.afterAlt')"
                    class="absolute inset-0 h-full w-full object-cover"
                />

                <template v-if="before">
                    <img
                        :src="before"
                        alt=""
                        aria-hidden="true"
                        class="pointer-events-none absolute inset-0 h-full w-full object-cover [clip-path:inset(0_calc(100%_-_var(--position))_0_0)]"
                    />
                    <span
                        aria-hidden="true"
                        class="pointer-events-none absolute inset-y-0 left-(--position) w-0.5 -translate-x-1/2 bg-chalk shadow-[0_0_4px_rgba(0,0,0,0.4)]"
                    >
                        <span
                            class="absolute top-1/2 left-1/2 flex size-10 -translate-1/2 items-center justify-center rounded-full border-2 border-chalk bg-ink/85 text-chalk shadow-[0_0_4px_rgba(0,0,0,0.4)] outline-offset-2 outline-chalk-deep group-has-[input:focus-visible]:outline-3"
                        >
                            <ChevronsLeftRight class="size-5" />
                        </span>
                    </span>
                    <span
                        aria-hidden="true"
                        class="pointer-events-none absolute top-4 left-4 rounded-full bg-white/90 px-3 py-2 text-[13px] font-semibold text-ink"
                        >{{ t('tryOn.compare.before') }}</span
                    >
                    <input
                        type="range"
                        min="0"
                        max="100"
                        step="1"
                        :value="position"
                        :aria-label="t('tryOn.compare.slider')"
                        :aria-valuetext="valueText"
                        class="absolute inset-0 m-0 h-full w-full cursor-ew-resize touch-pan-y appearance-none opacity-0"
                        @input="onSlide"
                    />
                </template>

                <span
                    aria-hidden="true"
                    class="pointer-events-none absolute top-4 right-4 rounded-full bg-ink/85 px-3 py-2 text-[13px] font-semibold text-chalk"
                    >{{ t('tryOn.compare.after') }}</span
                >
            </motion.div>
        </div>
        <p class="text-[13px] text-slate">
            {{ t('tryOn.compare.disclaimer') }}
        </p>
    </div>
</template>
