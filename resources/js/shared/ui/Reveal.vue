<script setup lang="ts">
// Block that rises into view on scroll (once). prefers-reduced-motion disables it through MotionConfig.
import { Motion } from 'motion-v';

// `as` is a union of concrete tag names: motion-v's Motion component is generic and rejects a plain `string`.
withDefaults(
    defineProps<{
        delay?: number;
        y?: number;
        as?: 'div' | 'li' | 'figure' | 'h2' | 'section';
    }>(),
    {
        delay: 0,
        y: 24,
        as: 'div',
    },
);
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
