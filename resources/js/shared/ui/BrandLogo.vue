<script setup lang="ts">
// Brend nomi — bitta joyda. Nom o'zgarsa (hozir "Boutique·AI" vaqtinchalik) faqat shu fayl o'zgaradi.
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

withDefaults(defineProps<{ size?: 'sm' | 'md' }>(), { size: 'md' });
</script>

<template>
    <Link
        :href="home()"
        class="font-display font-semibold tracking-[0.01em] text-[#EDEFF3]"
        :class="size === 'sm' ? 'text-[22px]' : 'text-[22px] md:text-[26px]'"
    >
        Boutique<span class="text-chalk">·</span>AI
    </Link>
</template>
