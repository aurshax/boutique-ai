<script setup lang="ts">
// Small product image (catalogue style: white background). Shows the type's initial until an image exists.
import type { Product } from '../model/types';

withDefaults(
    defineProps<{
        product: Pick<Product, 'name' | 'image' | 'type_label'>;
        size?: 'sm' | 'md';
    }>(),
    { size: 'sm' },
);
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[#E3E6EB] bg-white"
        :class="size === 'sm' ? 'size-11' : 'size-14'"
    >
        <img
            v-if="product.image"
            :src="product.image"
            :alt="product.name"
            loading="lazy"
            class="h-full w-full object-contain p-0.5"
        />
        <span v-else aria-hidden="true" class="font-mono text-xs text-slate">{{
            product.type_label.charAt(0)
        }}</span>
    </span>
</template>
