<script setup lang="ts">
// Site logo: the Fitzaro mark + wordmark, as in the plugin banner. The name comes from shared/config/brand.
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';
import { BRAND_NAME } from '@/shared/config/brand';
import BrandMark from '@/shared/ui/BrandMark.vue';

withDefaults(defineProps<{ size?: 'sm' | 'md' }>(), { size: 'md' });
</script>

<template>
    <Link
        :href="home()"
        class="inline-flex items-center gap-2 font-sans font-bold tracking-[-0.01em] text-[#EDEFF3]"
        :class="size === 'sm' ? 'text-[22px]' : 'text-[22px] md:text-[26px]'"
    >
        <BrandMark
            class="shrink-0"
            :class="size === 'sm' ? 'size-8' : 'size-8 md:size-9'"
        />
        {{ BRAND_NAME }}
    </Link>
</template>
