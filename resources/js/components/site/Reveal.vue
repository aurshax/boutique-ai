<script setup lang="ts">
// Skrollda pastdan ko'tarilib chiqadigan blok (bir marta). prefers-reduced-motion — MotionConfig orqali o'chadi.
import { Motion } from 'motion-v';

// `as` — aniq teg nomlari: motion-v Motion komponenti generik, oddiy `string` ni qabul qilmaydi.
withDefaults(defineProps<{ delay?: number; y?: number; as?: 'div' | 'li' | 'figure' | 'h2' | 'section' }>(), {
    delay: 0,
    y: 24,
    as: 'div',
});
</script>

<template>
    <Motion
        :as="as"
        :initial="{ opacity: 0, y }"
        :while-in-view="{ opacity: 1, y: 0 }"
        :in-view-options="{ once: true, amount: 0.2 }"
        :transition="{ duration: 0.7, delay, ease: [0.2, 0.8, 0.2, 1] }"
    >
        <slot />
    </Motion>
</template>
