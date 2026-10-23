<script setup lang="ts">
// Oldin/keyin surgich. Natija rasmi yuqoridan pastga "mato yoyilgandek" ochiladi (clip-path).
import { motion } from 'motion-v';
import { ref, useId } from 'vue';

defineProps<{ before: string | null; after: string }>();
const split = ref(55);
const sliderId = useId();
</script>

<template>
    <div class="flex flex-col gap-3.5">
        <div
            class="relative aspect-[4/5] overflow-hidden rounded-3xl bg-[#DDE1E7] lg:aspect-auto lg:h-[760px]"
        >
            <img
                v-if="before"
                :src="before"
                alt="Oldin: sizning rasmingiz"
                class="absolute inset-0 h-full w-full object-cover object-[center_15%]"
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
                    alt="Keyin: tanlangan look'dagi natija"
                    class="h-full w-full object-cover object-[center_15%]"
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
                >Keyin</span
            >
            <span
                v-if="before"
                class="absolute top-4 right-4 rounded-full bg-white/90 px-3 py-2 text-[13px] font-semibold text-ink"
                >Oldin</span
            >
        </div>
        <div v-if="before" class="flex items-center gap-3.5">
            <label :for="sliderId" class="text-sm whitespace-nowrap text-slate"
                >Solishtirish</label
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
            AI tasviri. Haqiqiy rang va naqsh biroz farq qilishi mumkin —
            do'konda tkan namunasini ko'ring.
        </p>
    </div>
</template>
