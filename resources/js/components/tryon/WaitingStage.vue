<script setup lang="ts">
/**
 * Kutish: rasm atrofida tikuv chizig'i taxminiy vaqt bo'yicha chiziladi (95% da to'xtaydi —
 * haqiqiy tugashni server aytadi), status matnlari almashadi, taymer.
 */
import { motion } from 'motion-v';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ photoUrl: string | null; status: string; expectedSeconds?: number; summary: string }>();
defineEmits<{ cancel: [] }>();

const expected = computed(() => props.expectedSeconds ?? 30);
const elapsed = ref(0);
let tick: ReturnType<typeof setInterval> | null = null;
onMounted(() => {
    tick = setInterval(() => elapsed.value++, 1000);
});
onBeforeUnmount(() => {
    if (tick) clearInterval(tick);
});

const progress = computed(() => Math.min(0.95, elapsed.value / expected.value));
const mmss = (s: number): string => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, '0')}`;

const messages = ["Rasm tayyorlanmoqda…", 'Bichim moslanmoqda…', 'Mato tushirilmoqda…', 'Choklar tikilmoqda…', 'Yorug\'lik moslanmoqda…'];
const message = computed(() => (props.status === 'queued' ? 'Navbatda…' : messages[Math.floor(elapsed.value / 4) % messages.length]));

const steps = computed(() => [
    { label: "Rasm tozalandi va kichraytirildi", state: 'done' },
    { label: "Look va tkan tayyorlandi", state: 'done' },
    { label: 'Kostyum tikilmoqda', state: props.status === 'running' ? 'active' : 'pending' },
    { label: 'Natija', state: 'pending' },
]);
</script>

<template>
    <section aria-live="polite" class="mx-auto flex w-full max-w-[520px] flex-col gap-5">
        <div
            class="relative aspect-[4/5] overflow-hidden rounded-[22px] bg-ink-2 bg-cover bg-[center_20%]"
            :style="photoUrl ? { backgroundImage: `linear-gradient(180deg, rgba(15,23,38,0.35), rgba(15,23,38,0.8)), url(${photoUrl})` } : {}"
        >
            <svg aria-hidden="true" viewBox="0 0 350 500" preserveAspectRatio="none" class="absolute inset-0 h-full w-full">
                <rect x="10" y="10" width="330" height="480" rx="16" fill="none" stroke="rgba(229,196,107,0.18)" stroke-width="2" stroke-dasharray="8 6" />
                <motion.rect
                    x="10" y="10" width="330" height="480" rx="16" fill="none" stroke="#E5C46B" stroke-width="2.5"
                    :initial="{ pathLength: 0 }"
                    :animate="{ pathLength: progress }"
                    :transition="{ duration: 1, ease: 'linear' }"
                />
            </svg>
            <div class="absolute inset-x-0 bottom-0 flex flex-col items-center gap-2.5 p-6 text-center text-[#EDEFF3]">
                <p class="font-mono text-xs tracking-[0.14em] text-chalk uppercase">AI tikmoqda · {{ summary }}</p>
                <motion.p
                    :key="message"
                    class="font-display text-[30px] leading-tight"
                    :initial="{ opacity: 0, y: 8 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.4 }"
                >{{ message }}</motion.p>
                <p class="font-mono text-[22px] tabular-nums">{{ mmss(elapsed) }} <span class="text-sm text-fog">/ ~{{ mmss(expected) }}</span></p>
            </div>
        </div>

        <ol class="flex flex-col gap-2.5 text-[15px] text-ink">
            <li v-for="s in steps" :key="s.label" class="flex items-center gap-3" :class="s.state === 'pending' ? 'text-slate' : s.state === 'active' ? 'font-semibold' : ''">
                <span
                    aria-hidden="true"
                    class="flex size-[22px] items-center justify-center rounded-full text-[13px] font-bold"
                    :class="s.state === 'done' ? 'bg-[#2E7D5B] text-white' : s.state === 'active' ? 'animate-pulse border-2 border-chalk-deep' : 'border-[1.5px] border-dashed border-slate'"
                >{{ s.state === 'done' ? '✓' : '' }}</span>
                {{ s.label }}
            </li>
        </ol>

        <div class="flex flex-col gap-2">
            <button type="button" class="rounded-full border border-[#C2C8D2] px-4 py-3.5 text-[15px] hover:border-ink" @click="$emit('cancel')">
                Kutishni to'xtatish
            </button>
            <p class="text-center text-xs leading-relaxed text-slate">
                So'rov allaqachon AI'ga yuborilgan — to'xtatsangiz ham to'lov yechilishi mumkin. Rasmingiz o'chiriladi.
            </p>
        </div>
    </section>
</template>
