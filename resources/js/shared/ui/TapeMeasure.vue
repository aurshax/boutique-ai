<script setup lang="ts">
// Tailor's tape: draws in from left to right, then the marks light up one by one.
import { motion } from 'motion-v';

withDefaults(defineProps<{ marks: (number | string)[]; unit?: string }>(), {
    unit: 'sm',
});
</script>

<template>
    <div
        aria-hidden="true"
        class="relative h-14 border-t border-chalk/35 bg-ink/85 text-chalk"
    >
        <motion.div
            class="tape-measure absolute inset-x-0 top-0 h-[22px] origin-left"
            :initial="{ scaleX: 0 }"
            :animate="{ scaleX: 1 }"
            :transition="{
                duration: 1.4,
                delay: 0.6,
                ease: [0.65, 0, 0.35, 1],
            }"
        />
        <div
            class="mx-auto flex max-w-[1360px] justify-between px-5 pt-[26px] font-mono text-xs tracking-wider md:px-10"
        >
            <motion.span
                v-for="(mark, i) in marks"
                :key="mark"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                :transition="{ delay: 0.9 + i * 0.12, duration: 0.3 }"
                :class="i > 0 && i < marks.length - 1 ? '' : 'hidden sm:inline'"
            >
                {{ mark
                }}<template v-if="i === Math.floor(marks.length / 2)">
                    {{ unit }}</template
                >
            </motion.span>
        </div>
    </div>
</template>

<style scoped>
/* Santimetr lentasi: mayda chiziq har 12px, katta chiziq har 60px */
.tape-measure {
    background-image:
        repeating-linear-gradient(
            90deg,
            currentColor 0 1px,
            transparent 1px 12px
        ),
        repeating-linear-gradient(
            90deg,
            currentColor 0 1px,
            transparent 1px 60px
        );
    background-size:
        auto 10px,
        auto 22px;
    background-repeat: repeat-x;
}
</style>
