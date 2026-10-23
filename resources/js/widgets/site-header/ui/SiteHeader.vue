<script setup lang="ts">
// Home header: floats over the hero (absolute), so no gap appears above the hero whatever its height.
import { Link } from '@inertiajs/vue3';
import { create as tryOn } from '@/routes/try-on';
import { useI18n } from '@/shared/i18n';
import BrandLogo from '@/shared/ui/BrandLogo.vue';
import { Button } from '@/shared/ui/button';

const { t } = useI18n();

// Home page sections (widgets/home-sections) — the ids must match this list.
const links = [
    { href: '#how', labelKey: 'nav.howItWorks' },
    { href: '#styles', labelKey: 'nav.styles' },
    { href: '#fabrics', labelKey: 'nav.fabrics' },
    { href: '#trust', labelKey: 'nav.trust' },
];
</script>

<template>
    <header
        class="absolute inset-x-0 top-0 z-20 mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-6 px-5 py-5 md:px-10 md:py-6"
    >
        <BrandLogo />
        <nav
            :aria-label="t('nav.main')"
            class="hidden gap-8 text-[15px] text-mist lg:flex"
        >
            <a
                v-for="link in links"
                :key="link.href"
                :href="link.href"
                class="hover:text-chalk"
                >{{ t(link.labelKey) }}</a
            >
        </nav>
        <Button as-child variant="chalk" size="pill-sm">
            <Link :href="tryOn()">{{ t('nav.tryOn') }}</Link>
        </Button>
    </header>
</template>
