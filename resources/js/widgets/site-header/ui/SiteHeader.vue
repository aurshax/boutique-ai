<script setup lang="ts">
// Home header: hero ustida "suzadi" (absolute) — balandligi o'zgarsa ham hero tepasida bo'shliq qolmaydi.
import { Link } from '@inertiajs/vue3';
import { create as tryOn } from '@/routes/try-on';
import BrandLogo from '@/shared/ui/BrandLogo.vue';
import { Button } from '@/shared/ui/button';

// Home sahifasidagi bo'limlar (widgets/home-sections) — id'lar shu ro'yxat bilan mos.
const links = [
    { href: '#how', label: 'Qanday ishlaydi' },
    { href: '#styles', label: 'Uslublar' },
    { href: '#fabrics', label: 'Tkanlar' },
    { href: '#trust', label: 'Xavfsizlik' },
];
</script>

<template>
    <header
        class="absolute inset-x-0 top-0 z-20 mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-6 px-5 py-5 md:px-10 md:py-6"
    >
        <BrandLogo />
        <nav
            aria-label="Asosiy menyu"
            class="hidden gap-8 text-[15px] text-mist lg:flex"
        >
            <a
                v-for="link in links"
                :key="link.href"
                :href="link.href"
                class="hover:text-chalk"
                >{{ link.label }}</a
            >
        </nav>
        <Button as-child variant="chalk" size="pill-sm">
            <Link :href="tryOn()">Kiyinib ko'ring</Link>
        </Button>
    </header>
</template>
