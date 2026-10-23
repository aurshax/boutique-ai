<script setup lang="ts">
/**
 * Home hero: fon video (desktop gorizontal, mobil vertikal), sekin zoom, so'zma-so'z sarlavha,
 * sichqoncha bilan parallax. prefers-reduced-motion bo'lsa — video o'rniga poster, harakat yo'q.
 */
import { Link } from '@inertiajs/vue3';
import { motion, useReducedMotion } from 'motion-v';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import TapeMeasure from '@/components/site/TapeMeasure.vue';

const props = defineProps<{ estimate: string }>();

const title = "Kostyumni sotib olishdan oldin — o'zingizda ko'ring.";
const words = title.split(' ');

const reduceMotion = useReducedMotion();
const isMobile = ref(false);
const root = ref<HTMLElement | null>(null);

// Parallax: CSS o'zgaruvchilari orqali (Vue qayta render qilmaydi, faqat transform).
let frame = 0;
function onPointerMove(event: PointerEvent): void {
    if (reduceMotion.value || !root.value || event.pointerType !== 'mouse') return;
    const rect = root.value.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width - 0.5;
    const y = (event.clientY - rect.top) / rect.height - 0.5;
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(() => {
        root.value?.style.setProperty('--px', x.toFixed(3));
        root.value?.style.setProperty('--py', y.toFixed(3));
    });
}

let mq: MediaQueryList | null = null;
const syncMobile = (): void => {
    isMobile.value = mq?.matches ?? false;
};

onMounted(() => {
    mq = window.matchMedia('(max-width: 767px)');
    syncMobile();
    mq.addEventListener('change', syncMobile);
});
onBeforeUnmount(() => {
    mq?.removeEventListener('change', syncMobile);
    cancelAnimationFrame(frame);
});

const video = computed(() =>
    isMobile.value
        ? { poster: '/media/hero/hero-chalk-mobile-poster.webp', sources: [{ src: '/media/hero/hero-chalk-mobile.mp4', type: 'video/mp4' }] }
        : {
              poster: '/media/hero/hero-fitting-poster.webp',
              sources: [
                  { src: '/media/hero/hero-fitting-1080.webm', type: 'video/webm' },
                  { src: '/media/hero/hero-fitting-1080.mp4', type: 'video/mp4' },
              ],
          },
);
</script>

<template>
    <section
        ref="root"
        class="relative flex min-h-[100svh] flex-col justify-end overflow-hidden md:min-h-[860px]"
        @pointermove="onPointerMove"
    >
        <!-- Fon: video yoki poster -->
        <div
            class="absolute inset-[-16px] transition-transform duration-300 ease-out"
            style="transform: translate3d(calc(var(--px, 0) * -16px), calc(var(--py, 0) * -10px), 0)"
            aria-hidden="true"
        >
            <motion.div
                class="h-full w-full"
                :initial="{ scale: 1 }"
                :animate="reduceMotion ? { scale: 1 } : { scale: 1.06 }"
                :transition="{ duration: 12, ease: 'linear', repeat: Infinity, repeatType: 'reverse' }"
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
                    <source v-for="s in video.sources" :key="s.src" :src="s.src" :type="s.type" />
                </video>
            </motion.div>
        </div>

        <!-- O'qilishi uchun qatlam: desktop chapdan, mobil pastdan -->
        <div
            class="absolute inset-0 bg-[linear-gradient(180deg,rgba(15,23,38,0.5)_0%,rgba(15,23,38,0.1)_30%,rgba(15,23,38,0.92)_72%,#0f1726_100%)] md:bg-[linear-gradient(90deg,rgba(15,23,38,0.94)_0%,rgba(15,23,38,0.78)_38%,rgba(15,23,38,0.15)_72%)]"
            aria-hidden="true"
        />

        <div
            class="relative mx-auto flex w-full max-w-[1360px] flex-col gap-5 px-5 pb-8 md:gap-7 md:px-10 md:pb-[72px]"
            style="transform: translate3d(calc(var(--px, 0) * 6px), calc(var(--py, 0) * 4px), 0)"
        >
            <motion.p
                class="font-mono text-[11px] tracking-[0.16em] text-chalk uppercase md:text-[13px]"
                :initial="{ opacity: 0 }"
                :animate="{ opacity: 1 }"
                :transition="{ duration: 0.6 }"
            >
                Virtual primerka · erkaklar kostyumi
            </motion.p>

            <h1
                class="max-w-[760px] font-display text-[42px] leading-[1.02] font-medium tracking-[-0.01em] text-balance md:text-[84px] md:leading-[0.98]"
                :aria-label="title"
            >
                <span v-for="(word, i) in words" :key="i" class="inline-block overflow-hidden pb-[0.08em] align-bottom" aria-hidden="true">
                    <motion.span
                        class="inline-block"
                        :initial="{ y: '110%' }"
                        :animate="{ y: '0%' }"
                        :transition="{ type: 'spring', stiffness: 120, damping: 18, delay: 0.15 + i * 0.06 }"
                    >{{ word }}&nbsp;</motion.span>
                </span>
            </h1>

            <motion.p
                class="max-w-[560px] text-base leading-relaxed text-mist md:text-[19px]"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ delay: 0.15 + words.length * 0.06, duration: 0.6 }"
            >
                Rasmingizni yuklang va uslubni tanlang. Shim, ko'ylak va aksessuarlar bo'yingizga moslab
                kiyintiriladi — istagan tkaningizda.
            </motion.p>

            <motion.div
                class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3.5"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ delay: 0.3 + words.length * 0.06, duration: 0.6 }"
            >
                <Link
                    href="/try-on"
                    class="rounded-full bg-chalk px-[30px] py-4 text-center text-[17px] font-semibold text-ink transition-colors hover:bg-chalk-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-chalk"
                >
                    Kiyinib ko'ring
                </Link>
                <a
                    href="#how"
                    class="hidden rounded-full border border-white/35 px-7 py-[15px] text-[17px] transition-colors hover:border-chalk hover:text-chalk sm:inline-block"
                >
                    Qanday ishlaydi
                </a>
                <span class="text-center text-[13px] text-fog sm:ml-1.5 sm:text-left sm:text-sm">
                    Bitta look ≈ {{ props.estimate }}, o'z OpenAI kalitingiz bilan
                </span>
            </motion.div>
        </div>

        <TapeMeasure :marks="[160, 166, 172, 178, 184, 190, 196]" />
    </section>
</template>
