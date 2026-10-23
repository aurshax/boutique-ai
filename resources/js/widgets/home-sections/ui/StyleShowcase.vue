<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { StyleCard } from '@/entities/catalog';
import { create as tryOn } from '@/routes/try-on';
import { formatUzs } from '@/shared/lib/format';
import Reveal from '@/shared/ui/Reveal.vue';

defineProps<{ styles: StyleCard[] }>();
</script>

<template>
    <section
        id="styles"
        class="mx-auto flex max-w-[1360px] scroll-mt-8 flex-col gap-10 px-5 pt-24 pb-10 md:px-10"
    >
        <Reveal class="flex flex-wrap items-end justify-between gap-6">
            <h2
                class="font-display text-4xl leading-[1.05] font-medium md:text-[52px]"
            >
                Uslubni tanlang
            </h2>
            <Link
                :href="tryOn()"
                class="text-base text-chalk hover:text-chalk-hover"
                >Barcha look'lar →</Link
            >
        </Reveal>
        <div class="grid grid-cols-2 gap-4 md:gap-5 lg:grid-cols-4">
            <Reveal
                v-for="(style, i) in styles"
                :key="style.slug"
                :delay="i * 0.08"
            >
                <Link :href="tryOn()" class="group flex flex-col gap-3.5">
                    <div
                        class="relative aspect-[3/4] overflow-hidden rounded-2xl bg-ink-2"
                    >
                        <img
                            v-if="style.cover_image"
                            :src="style.cover_image"
                            :alt="`${style.name} uslubidagi kostyum`"
                            loading="lazy"
                            class="h-full w-full object-cover transition-transform duration-700 ease-[cubic-bezier(.2,.8,.2,1)] group-hover:scale-[1.04]"
                        />
                        <!-- hover: tikuv chizig'i -->
                        <span
                            class="pointer-events-none absolute inset-2.5 rounded-xl border-[1.5px] border-dashed border-chalk opacity-0 transition-opacity duration-500 group-hover:opacity-100"
                            aria-hidden="true"
                        />
                    </div>
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-lg font-semibold md:text-xl">{{
                            style.name
                        }}</span>
                        <span class="font-mono text-[13px] text-fog"
                            >{{ style.looks_count }} look</span
                        >
                    </div>
                    <span v-if="style.from_price_uzs" class="text-sm text-fog"
                        >{{ formatUzs(style.from_price_uzs) }} so'mdan</span
                    >
                </Link>
            </Reveal>
        </div>
    </section>
</template>
