<script setup lang="ts">
/**
 * Home hero: background video (landscape on desktop, portrait on mobile), slow zoom, word-by-word
 * title, pointer parallax. With prefers-reduced-motion: a poster instead of the video, no motion.
 */
import { Link } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { motion, useReducedMotion } from 'motion-v';
import { computed, ref } from 'vue';
import { create as tryOn } from '@/routes/try-on';
import { useI18n } from '@/shared/i18n';
import { usePointerParallax } from '@/shared/lib/composables/usePointerParallax';
import TapeMeasure from '@/shared/ui/TapeMeasure.vue';
import { Button } from '@/shared/ui/button';
import { HERO_MEDIA, TAPE_MARKS } from '../model/media';

/** estimate — preformatted text, e.g. "$0.05–0.09" (shared/lib/format → formatUsdRange). */
const props = defineProps<{ estimate: string }>();

const { t } = useI18n();
const title = computed(() => t('home.hero.title'));
const words = computed(() => title.value.split(' '));

const reduceMotion = useReducedMotion();
const isMobile = useMediaQuery('(max-width: 767px)');
const root = ref<HTMLElement | null>(null);

const { onPointerMove } = usePointerParallax(root, reduceMotion);

const video = computed(() =>
    isMobile.value ? HERO_MEDIA.mobile : HERO_MEDIA.desktop,
);
</script>

<template>
    <section
        ref="root"
        class="relative flex min-h-[100svh] flex-col justify-end overflow-hidden md:min-h-[860px]"
        @pointermove="onPointerMove"
    >
        <!-- Background: video or poster -->
        <div
            class="absolute inset-[-16px] transition-transform duration-300 ease-out"
            style="
                transform: translate3d(
                    calc(var(--px, 0) * -16px),
                    calc(var(--py, 0) * -10px),
                    0
                );
            "
            aria-hidden="true"
        >
            <motion.div
                class="h-full w-full"
                :initial="{ scale: 1 }"
                :animate="reduceMotion ? { scale: 1 } : { scale: 1.06 }"
                :transition="{
                    duration: 12,
                    ease: 'linear',
                    repeat: Infinity,
                    repeatType: 'reverse',
                }"
            >
                <img
                    v-if="reduceMotion"
                    :src="video.poster"
                    alt=""
                    class="h-full w-full object-cover object-[center_30%]"
                />
                <video
                    v-else
                    :key="video.poster"
                    class="h-full w-full object-cover object-[center_30%]"
                    :poster="video.poster"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                    disablepictureinpicture
                >
                    <source
                        v-for="s in video.sources"
                        :key="s.src"
                        :src="s.src"
                        :type="s.type"
                    />
                </video>
            </motion.div>
        </div>

        <!-- Legibility scrim: from the left on desktop, from the bottom on mobile -->
        <div
            class="absolute inset-0 bg-[linear-gradient(180deg,rgba(15,23,38,0.5)_0%,rgba(15,23,38,0.1)_30%,rgba(15,23,38,0.92)_72%,#0f1726_100%)] md:bg-[linear-gradient(90deg,rgba(15,23,38,0.94)_0%,rgba(15,23,38,0.78)_38%,rgba(15,23,38,0.15)_72%)]"
            aria-hidden="true"
        />

        <div
            class="relative mx-auto flex w-full max-w-[1360px] flex-col gap-5 px-5 pb-8 md:gap-7 md:px-10 md:pb-[72px]"
            style="
                transform: translate3d(
                    calc(var(--px, 0) * 6px),
                    calc(var(--py, 0) * 4px),
                    0
                );
            "
        >
            <motion.p
                class="font-mono text-[11px] tracking-[0.16em] text-chalk uppercase md:text-[13px]"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                :transition="{ duration: 0.6 }"
            >
                {{ t('home.hero.eyebrow') }}
            </motion.p>

            <h1
                class="max-w-[760px] font-display text-[42px] leading-[1.02] font-medium tracking-[-0.01em] text-balance md:text-[84px] md:leading-[0.98]"
                :aria-label="title"
            >
                <span
                    v-for="(word, i) in words"
                    :key="i"
                    class="inline-block overflow-hidden pb-[0.08em] align-bottom"
                    aria-hidden="true"
                >
                    <motion.span
                        class="inline-block"
                        :initial="{ y: '110%' }"
                        :animate="{ y: '0%' }"
                        :transition="{
                            type: 'spring',
                            stiffness: 120,
                            damping: 18,
                            delay: 0.15 + i * 0.06,
                        }"
                        >{{ word }}&nbsp;</motion.span
                    >
                </span>
            </h1>

            <motion.p
                class="max-w-[560px] text-base leading-relaxed text-mist md:text-[19px]"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{
                    delay: 0.15 + words.length * 0.06,
                    duration: 0.6,
                }"
            >
                {{ t('home.hero.lead') }}
            </motion.p>

            <motion.div
                class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3.5"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{
                    delay: 0.3 + words.length * 0.06,
                    duration: 0.6,
                }"
            >
                <Button as-child variant="chalk" size="pill-lg">
                    <Link :href="tryOn()">{{ t('home.hero.cta') }}</Link>
                </Button>
                <Button
                    as-child
                    variant="pill-outline-dark"
                    size="pill-lg"
                    class="hidden sm:inline-flex"
                >
                    <a href="#how">{{ t('home.hero.how') }}</a>
                </Button>
                <span
                    class="text-center text-[13px] text-fog sm:ml-1.5 sm:text-left sm:text-sm"
                >
                    {{ t('home.hero.estimate', { estimate: props.estimate }) }}
                </span>
            </motion.div>
        </div>

        <TapeMeasure :marks="TAPE_MARKS" />
    </section>
</template>
