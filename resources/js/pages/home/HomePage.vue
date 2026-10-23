<script setup lang="ts">
// Home: faqat kompozitsiya — bo'limlar widgets/ ichida. Props: HomeController.
import { Head } from '@inertiajs/vue3';
import { MotionConfig } from 'motion-v';
import type { FabricCard, StyleCard } from '@/entities/catalog';
import { formatUsdRange } from '@/shared/lib/format';
import { HomeHero } from '@/widgets/home-hero';
import {
    CallToAction,
    FabricShowcase,
    HowItWorks,
    StyleShowcase,
    TrustSection,
} from '@/widgets/home-sections';
import { SiteFooter } from '@/widgets/site-footer';
import { SiteHeader } from '@/widgets/site-header';

const props = defineProps<{
    styles: StyleCard[];
    fabrics: FabricCard[];
    estimateUsd: { medium: number; high: number };
}>();

const estimate = formatUsdRange(
    props.estimateUsd.medium,
    props.estimateUsd.high,
);
</script>

<template>
    <Head title="Kostyumni o'zingizda ko'ring" />

    <MotionConfig reduced-motion="user">
        <div
            class="relative min-h-screen bg-ink font-body text-[#EDEFF3] antialiased"
        >
            <SiteHeader />
            <HomeHero :estimate="estimate" />
            <HowItWorks />
            <StyleShowcase :styles="styles" />
            <FabricShowcase :fabrics="fabrics" />
            <TrustSection />
            <CallToAction />
            <SiteFooter />
        </div>
    </MotionConfig>
</template>
