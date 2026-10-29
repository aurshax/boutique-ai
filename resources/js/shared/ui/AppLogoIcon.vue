<script setup lang="ts">
// Account pages (starter kit layouts, behind AUTH_ENABLED): the Fitzaro mark in the current text colour.
import type { HTMLAttributes } from 'vue';
import BrandMark from '@/shared/ui/BrandMark.vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <BrandMark :class="className" v-bind="$attrs" />
</template>
