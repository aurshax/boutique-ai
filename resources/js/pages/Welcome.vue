<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MotionConfig } from 'motion-v';
import HomeHero from '@/components/site/HomeHero.vue';
import Reveal from '@/components/site/Reveal.vue';

type StyleCard = {
    slug: string;
    name: string;
    description: string | null;
    cover_image: string | null;
    looks_count: number;
    from_price_uzs: number | null;
};

type FabricCard = {
    slug: string;
    name: string;
    composition: string;
    swatch_image: string;
    extra_price_uzs: number;
};

const props = defineProps<{
    styles: StyleCard[];
    fabrics: FabricCard[];
    estimateUsd: { medium: number; high: number };
}>();

const uzs = (n: number): string => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const estimate = `$${props.estimateUsd.medium.toFixed(2)}–${props.estimateUsd.high.toFixed(2)}`;

const steps = [
    { no: '01', title: "Rasm va bo'y", text: "To'liq bo'yli rasm yuklang, bo'yingiz va odatdagi razmeringizni kiriting. Rasm metama'lumotlardan tozalanadi." },
    { no: '02', title: 'Uslub va look', text: "Classic, Office, Smart Casual yoki Yozgi. Har look'da shim, ko'ylak yoki blazer va aksessuarlar — narxi bilan." },
    { no: '03', title: 'Natija va tkan', text: "O'zingizni yangi kostyumda ko'ring. Yoqmasa — shimni boshqa tkanda qayta ko'ring, rasmni qayta yuklamasdan." },
];

const trust = [
    { icon: 'clock', title: "Rasm 24 soatda o'chadi", text: 'Yuklangan rasm GPS va metama\'lumotlardan tozalanadi. Istalgan payt "Rasmimni o\'chir" tugmasi bor.' },
    { icon: 'lock', title: 'Kalitingiz saqlanmaydi', text: 'OpenAI kaliti faqat brauzeringizda turadi va so\'rov paytida shifrlangan holda ishlatiladi.' },
    { icon: 'list', title: 'Narx oldindan ko\'rinadi', text: "Har generatsiyadan oldin taxminiy narx chiqadi. Avtomatik qayta urinish yo'q — ortiqcha to'lov ham yo'q." },
];

// Tkan vitrinasi: zinapoya effekti uchun har kartaga pastki chekinish.
const fabricOffsets = ['md:mb-0', 'md:mb-12', 'md:mb-24'];
</script>

<template>
    <Head title="Kostyumni o'zingizda ko'ring" />

    <MotionConfig reduced-motion="user">
        <div class="relative min-h-screen bg-ink font-body text-[#EDEFF3] antialiased">
            <!-- NAV -->
            <!-- Header hero ustida "suzadi" (absolute) — balandligi o'zgarsa ham hero tepasida bo'shliq qolmaydi -->
            <header class="absolute inset-x-0 top-0 z-20 mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-6 px-5 py-5 md:px-10 md:py-6">
                <Link href="/" class="font-display text-[22px] font-semibold tracking-[0.01em] md:text-[26px]">
                    Boutique<span class="text-chalk">·</span>AI
                </Link>
                <nav aria-label="Asosiy menyu" class="hidden gap-8 text-[15px] text-mist lg:flex">
                    <a href="#how" class="hover:text-chalk">Qanday ishlaydi</a>
                    <a href="#styles" class="hover:text-chalk">Uslublar</a>
                    <a href="#fabrics" class="hover:text-chalk">Tkanlar</a>
                    <a href="#trust" class="hover:text-chalk">Xavfsizlik</a>
                </nav>
                <Link
                    href="/try-on"
                    class="rounded-full bg-chalk px-5 py-3 text-[15px] font-semibold text-ink transition-colors hover:bg-chalk-hover"
                >
                    Kiyinib ko'ring
                </Link>
            </header>

            <HomeHero :estimate="estimate" />

            <!-- QANDAY ISHLAYDI -->
            <section id="how" class="mx-auto flex max-w-[1360px] scroll-mt-8 flex-col gap-12 px-5 pt-24 pb-10 md:px-10 md:pt-[120px]">
                <Reveal class="flex flex-wrap items-end justify-between gap-8">
                    <h2 class="max-w-[620px] font-display text-4xl leading-[1.05] font-medium text-balance md:text-[52px]">
                        Uch qadam — va siz ko'zgu oldidasiz.
                    </h2>
                    <p class="max-w-[420px] text-[17px] leading-relaxed text-fog">
                        Do'konga borishdan oldin fason, rang va tkanni o'z rasmingizda solishtiring. Har bir natija
                        taxminan 20–40 soniyada tayyor bo'ladi.
                    </p>
                </Reveal>
                <ol class="grid gap-6 md:grid-cols-3">
                    <Reveal
                        v-for="(step, i) in steps"
                        :key="step.no"
                        as="li"
                        :delay="i * 0.12"
                        class="flex min-h-[240px] flex-col gap-4 rounded-[18px] border-[1.5px] border-dashed border-chalk/55 p-8"
                    >
                        <span class="font-mono text-sm text-chalk">{{ step.no }}</span>
                        <h3 class="text-2xl font-semibold">{{ step.title }}</h3>
                        <p class="text-base leading-relaxed text-fog">{{ step.text }}</p>
                    </Reveal>
                </ol>
            </section>

            <!-- USLUBLAR -->
            <section id="styles" class="mx-auto flex max-w-[1360px] scroll-mt-8 flex-col gap-10 px-5 pt-24 pb-10 md:px-10">
                <Reveal class="flex flex-wrap items-end justify-between gap-6">
                    <h2 class="font-display text-4xl leading-[1.05] font-medium md:text-[52px]">Uslubni tanlang</h2>
                    <Link href="/try-on" class="text-base text-chalk hover:text-chalk-hover">Barcha look'lar →</Link>
                </Reveal>
                <div class="grid grid-cols-2 gap-4 md:gap-5 lg:grid-cols-4">
                    <Reveal v-for="(style, i) in styles" :key="style.slug" :delay="i * 0.08">
                        <Link href="/try-on" class="group flex flex-col gap-3.5">
                            <div class="relative aspect-[3/4] overflow-hidden rounded-2xl bg-ink-2">
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
                                <span class="text-lg font-semibold md:text-xl">{{ style.name }}</span>
                                <span class="font-mono text-[13px] text-fog">{{ style.looks_count }} look</span>
                            </div>
                            <span v-if="style.from_price_uzs" class="text-sm text-fog">{{ uzs(style.from_price_uzs) }} so'mdan</span>
                        </Link>
                    </Reveal>
                </div>
            </section>

            <!-- TKANLAR -->
            <section id="fabrics" class="mx-auto max-w-[1360px] scroll-mt-8 px-5 pt-24 pb-10 md:px-10">
                <div class="grid items-end gap-6 md:grid-cols-12">
                    <Reveal class="flex flex-col gap-5 pb-3 md:col-span-4">
                        <p class="font-mono text-[13px] tracking-[0.16em] text-chalk uppercase">Bitta fason · istalgan tkan</p>
                        <h2 class="font-display text-4xl leading-[1.05] font-medium md:text-5xl">Shimni boshqa tkanda ko'ring.</h2>
                        <p class="text-[17px] leading-relaxed text-fog">
                            Natija ekranida tkanni bosing — rasmingiz qayta yuklanmaydi, faqat shim yangi tkanda qayta
                            tikiladi. Narx oldindan ko'rsatiladi.
                        </p>
                    </Reveal>
                    <Reveal
                        v-for="(fabric, i) in fabrics"
                        :key="fabric.slug"
                        as="figure"
                        :delay="i * 0.12"
                        :class="['flex flex-col gap-3', i === fabrics.length - 1 ? 'md:col-span-2' : 'md:col-span-3', fabricOffsets[i] ?? '']"
                    >
                        <img
                            :src="fabric.swatch_image"
                            :alt="`${fabric.name} tkani`"
                            loading="lazy"
                            class="aspect-[3/4] w-full rounded-[14px] object-cover"
                        />
                        <figcaption class="flex justify-between gap-2 text-[15px]">
                            <span>{{ fabric.name }}</span>
                            <span v-if="fabric.extra_price_uzs" class="font-mono text-[13px] text-fog">+{{ uzs(fabric.extra_price_uzs) }}</span>
                        </figcaption>
                    </Reveal>
                </div>
            </section>

            <!-- XAVFSIZLIK -->
            <section id="trust" class="mx-auto max-w-[1360px] scroll-mt-8 px-5 pt-24 pb-10 md:px-10">
                <Reveal class="grid gap-10 rounded-3xl bg-ink-2 p-8 md:grid-cols-3 md:p-14">
                    <div v-for="item in trust" :key="item.title" class="flex flex-col gap-3">
                        <svg aria-hidden="true" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-chalk">
                            <template v-if="item.icon === 'clock'"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></template>
                            <template v-else-if="item.icon === 'lock'"><rect x="4" y="10" width="16" height="10" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></template>
                            <template v-else><path d="M4 7h16M4 12h10M4 17h7" /></template>
                        </svg>
                        <h3 class="text-xl font-semibold">{{ item.title }}</h3>
                        <p class="text-[15px] leading-relaxed text-fog">{{ item.text }}</p>
                    </div>
                </Reveal>
            </section>

            <!-- CTA -->
            <section class="mx-auto flex max-w-[1360px] flex-col items-start gap-7 px-5 pt-28 pb-24 md:px-10">
                <Reveal as="h2" class="max-w-[900px] font-display text-5xl leading-[1.02] font-medium md:text-[64px]">
                    Ko'zgu tayyor. Navbat — sizda.
                </Reveal>
                <Link
                    href="/try-on"
                    class="rounded-full bg-chalk px-[30px] py-4 text-[17px] font-semibold text-ink transition-colors hover:bg-chalk-hover"
                >
                    Kiyinib ko'ring
                </Link>
            </section>

            <footer class="border-t border-white/10">
                <div class="mx-auto flex max-w-[1360px] flex-wrap justify-between gap-4 px-5 py-7 text-[13px] text-[#7F8899] md:px-10">
                    <span>© 2026 Boutique·AI — test loyiha</span>
                    <span>Rasmlar: Unsplash · Video: Pexels</span>
                </div>
            </footer>
        </div>
    </MotionConfig>
</template>
