<script setup lang="ts">
import type { FabricCard } from '@/entities/catalog';
import { useI18n } from '@/shared/i18n';
import { formatUzs } from '@/shared/lib/format';
import Reveal from '@/shared/ui/Reveal.vue';
import { FABRIC_OFFSETS } from '../model/content';

defineProps<{ fabrics: FabricCard[] }>();
const { t } = useI18n();
</script>

<template>
    <section
        id="fabrics"
        class="mx-auto max-w-[1360px] scroll-mt-8 px-5 pt-24 pb-10 md:px-10"
    >
        <div class="grid items-end gap-6 md:grid-cols-12">
            <Reveal class="flex flex-col gap-5 pb-3 md:col-span-4">
                <p
                    class="font-mono text-[13px] tracking-[0.16em] text-chalk uppercase"
                >
                    {{ t('home.fabrics.eyebrow') }}
                </p>
                <h2
                    class="font-display text-4xl leading-[1.05] font-medium md:text-5xl"
                >
                    {{ t('home.fabrics.title') }}
                </h2>
                <p class="text-[17px] leading-relaxed text-fog">
                    {{ t('home.fabrics.lead') }}
                </p>
            </Reveal>
            <Reveal
                v-for="(fabric, i) in fabrics"
                :key="fabric.slug"
                as="figure"
                :delay="i * 0.12"
                :class="[
                    'flex flex-col gap-3',
                    i === fabrics.length - 1
                        ? 'md:col-span-2'
                        : 'md:col-span-3',
                    FABRIC_OFFSETS[i] ?? '',
                ]"
            >
                <img
                    :src="fabric.swatch_image"
                    :alt="t('home.fabrics.alt', { name: fabric.name })"
                    loading="lazy"
                    class="aspect-[3/4] w-full rounded-[14px] object-cover"
                />
                <figcaption class="flex justify-between gap-2 text-[15px]">
                    <span>{{ fabric.name }}</span>
                    <span
                        v-if="fabric.extra_price_uzs"
                        class="font-mono text-[13px] text-fog"
                        >+{{ formatUzs(fabric.extra_price_uzs) }}</span
                    >
                </figcaption>
            </Reveal>
        </div>
    </section>
</template>
