<script setup lang="ts">
/**
 * Step 2: waiting. A stitch line is drawn around the photo along the expected duration (stops at 95% —
 * the server decides when it is really done), status lines rotate, and a timer runs.
 */
import { motion } from 'motion-v';
import { computed } from 'vue';
import { useElapsedSeconds } from '@/shared/lib/composables/useElapsedSeconds';
import { useI18n } from '@/shared/i18n';
import { formatDuration } from '@/shared/lib/format';
import { Button } from '@/shared/ui/button';
import { injectTryOnFlow } from '../model/useTryOnFlow';

const { t, tm } = useI18n();
const { form, current, summary, usesRealAi, discardPhoto } = injectTryOnFlow();

/** Expected duration (s) — drives the progress line. Real AI ≈ 20–60 s, the demo mock a few seconds. */
const expectedSeconds = computed(() => (usesRealAi.value ? 45 : 5));
const photoUrl = form.photoUrl;
const status = computed(() => current.value?.status ?? 'queued');

const MESSAGE_COUNT = tm('tryOn.waiting.messages').length;
const MESSAGE_EVERY_S = 4;

const elapsed = useElapsedSeconds();
const progress = computed(() =>
    Math.min(0.95, elapsed.value / expectedSeconds.value),
);
const message = computed(() =>
    status.value === 'queued'
        ? t('tryOn.waiting.queued')
        : t(
              `tryOn.waiting.messages.${Math.floor(elapsed.value / MESSAGE_EVERY_S) % MESSAGE_COUNT}`,
          ),
);
const backdrop = computed(() =>
    photoUrl.value
        ? {
              backgroundImage: `linear-gradient(180deg, rgba(15,23,38,0.35), rgba(15,23,38,0.8)), url(${photoUrl.value})`,
          }
        : {},
);

type StepState = 'done' | 'active' | 'pending';
const steps = computed<{ label: string; state: StepState }[]>(() => [
    { label: t('tryOn.waiting.steps.0'), state: 'done' },
    { label: t('tryOn.waiting.steps.1'), state: 'done' },
    {
        label: t('tryOn.waiting.steps.2'),
        state: status.value === 'running' ? 'active' : 'pending',
    },
    { label: t('tryOn.waiting.steps.3'), state: 'pending' },
]);
const markerClass: Record<StepState, string> = {
    done: 'bg-[#2E7D5B] text-white',
    active: 'animate-pulse border-2 border-chalk-deep',
    pending: 'border-[1.5px] border-dashed border-slate',
};
</script>

<template>
    <section
        aria-live="polite"
        class="mx-auto flex w-full max-w-[520px] flex-col gap-5 px-5 py-10"
    >
        <div
            class="relative aspect-[4/5] overflow-hidden rounded-[22px] bg-ink-2 bg-cover bg-[center_20%]"
            :style="backdrop"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 350 500"
                preserveAspectRatio="none"
                class="absolute inset-0 h-full w-full"
            >
                <rect
                    x="10"
                    y="10"
                    width="330"
                    height="480"
                    rx="16"
                    fill="none"
                    stroke="rgba(229,196,107,0.18)"
                    stroke-width="2"
                    stroke-dasharray="8 6"
                />
                <motion.rect
                    x="10"
                    y="10"
                    width="330"
                    height="480"
                    rx="16"
                    fill="none"
                    stroke="#E5C46B"
                    stroke-width="2.5"
                    :initial="{ pathLength: 0 }"
                    :animate="{ pathLength: progress }"
                    :transition="{ duration: 1, ease: 'linear' }"
                />
            </svg>
            <div
                class="absolute inset-x-0 bottom-0 flex flex-col items-center gap-2.5 p-6 text-center text-[#EDEFF3]"
            >
                <p
                    class="font-mono text-xs tracking-[0.14em] text-chalk uppercase"
                >
                    {{ t('tryOn.waiting.eyebrow', { summary }) }}
                </p>
                <motion.p
                    :key="message"
                    class="font-display text-[30px] leading-tight"
                    :initial="{ opacity: 0, y: 8 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.4 }"
                    >{{ message }}</motion.p
                >
                <p class="font-mono text-[22px] tabular-nums">
                    {{ formatDuration(elapsed) }}
                    <span class="text-sm text-fog"
                        >/ ~{{ formatDuration(expectedSeconds) }}</span
                    >
                </p>
            </div>
        </div>

        <ol class="flex flex-col gap-2.5 text-[15px] text-ink">
            <li
                v-for="s in steps"
                :key="s.label"
                class="flex items-center gap-3"
                :class="{
                    'text-slate': s.state === 'pending',
                    'font-semibold': s.state === 'active',
                }"
            >
                <span
                    aria-hidden="true"
                    class="flex size-[22px] items-center justify-center rounded-full text-[13px] font-bold"
                    :class="markerClass[s.state]"
                >
                    {{ s.state === 'done' ? '✓' : '' }}
                </span>
                {{ s.label }}
            </li>
        </ol>

        <div class="flex flex-col gap-2">
            <Button variant="pill-outline" size="pill" @click="discardPhoto">{{
                t('tryOn.waiting.stop')
            }}</Button>
            <p
                v-if="usesRealAi"
                class="text-center text-xs leading-relaxed text-slate"
            >
                {{ t('tryOn.waiting.stopNote') }}
            </p>
        </div>
    </section>
</template>
