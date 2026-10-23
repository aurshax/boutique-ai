<script setup lang="ts">
/**
 * Try-on sahifasi: 1) kalit (OpenAI rejimida) → 2) rasm, bo'y, look, tkan → 3) kutish → 4) natija.
 * Holat sahifa ichida (step), server bilan aloqa — useTryOnRequest (fetch + polling).
 */
import { Head, Link } from '@inertiajs/vue3';
import { AnimatePresence, MotionConfig, motion } from 'motion-v';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import BeforeAfter from '@/components/tryon/BeforeAfter.vue';
import FabricSwatches from '@/components/tryon/FabricSwatches.vue';
import HeightTape from '@/components/tryon/HeightTape.vue';
import LookPicker from '@/components/tryon/LookPicker.vue';
import PhotoDrop from '@/components/tryon/PhotoDrop.vue';
import WaitingStage from '@/components/tryon/WaitingStage.vue';
import { useApiKey } from '@/composables/useApiKey';
import { useTryOnRequest } from '@/composables/useTryOnRequest';
import type { Quality, Style } from '@/types/tryon';

const props = defineProps<{
    styles: Style[];
    sizes: string[];
    provider: string;
    requiresApiKey: boolean;
    estimateUsd: Record<Quality, number>;
}>();

const uzs = (n: number): string => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

// ---- Kalit ----
const apiKey = useApiKey();
const keyDraft = ref(apiKey.key.value);
const rememberDraft = ref(apiKey.remember.value);
const keyReady = computed(() => !props.requiresApiKey || apiKey.isValidFormat.value);

// ---- Forma holati ----
const photo = ref<File | null>(null);
const photoUrl = ref<string | null>(null);
const heightCm = ref(178);
const waistCm = ref<number | null>(null);
const size = ref<string | null>('48');
const styleId = ref(props.styles[2]?.id ?? props.styles[0]?.id ?? 0);
const lookId = ref(props.styles.find((s) => s.id === styleId.value)?.looks[0]?.id ?? 0);
const fabricId = ref<number | null>(null);
const quality = ref<Quality>('medium');
const consent = ref(false);

const look = computed(() => props.styles.flatMap((s) => s.looks).find((l) => l.id === lookId.value) ?? null);
const lookStyle = computed(() => props.styles.find((s) => s.looks.some((l) => l.id === lookId.value)) ?? null);
const trousers = computed(() => look.value?.products.find((p) => p.type === 'trousers') ?? null);
const fabrics = computed(() => trousers.value?.fabrics ?? []);
const fabric = computed(() => fabrics.value.find((f) => f.id === fabricId.value) ?? null);
const total = computed(() => (look.value?.total_price_uzs ?? 0) + (fabric.value?.extra_price_uzs ?? 0));

// Look almashsa — shimning birinchi (narx qo'shmaydigan) tkani tanlanadi.
watch(lookId, () => {
    fabricId.value = fabrics.value.find((f) => f.extra_price_uzs === 0)?.id ?? fabrics.value[0]?.id ?? null;
}, { immediate: true });

function selectPhoto(file: File): void {
    if (photoUrl.value) URL.revokeObjectURL(photoUrl.value);
    photo.value = file;
    photoUrl.value = URL.createObjectURL(file);
    sourceRequestId.value = null;   // yangi rasm — eski so'rovga bog'lanmaydi
}
onBeforeUnmount(() => {
    if (photoUrl.value) URL.revokeObjectURL(photoUrl.value);
});

// ---- So'rov ----
const tryOn = useTryOnRequest();
const step = ref<'setup' | 'waiting' | 'result'>('setup');
const sourceRequestId = ref<string | null>(null);   // tkan almashtirishda rasmni qayta yuklamaslik uchun
const swapFabricId = ref<number | null>(null);

const canSubmit = computed(() => keyReady.value && (photo.value !== null || sourceRequestId.value !== null) && consent.value && lookId.value > 0 && !tryOn.submitting.value);

async function generate(overrideFabricId?: number | null): Promise<void> {
    if (overrideFabricId !== undefined) fabricId.value = overrideFabricId;
    const ok = await tryOn.submit({
        photo: sourceRequestId.value ? null : photo.value,
        sourceRequestId: sourceRequestId.value,
        heightCm: heightCm.value,
        waistCm: waistCm.value,
        size: size.value,
        lookId: lookId.value,
        fabricId: fabricId.value,
        quality: quality.value,
        consent: consent.value,
        apiKey: props.requiresApiKey ? apiKey.key.value : null,
    });
    if (ok) {
        step.value = 'waiting';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

// Server holati → qadam
watch(() => tryOn.current.value?.status, (status) => {
    if (status === 'done') {
        step.value = 'result';
        sourceRequestId.value = tryOn.current.value?.id ?? null;
        swapFabricId.value = fabricId.value;
    } else if (status === 'failed' || status === 'cancelled') {
        step.value = 'setup';
    }
});

// Kutishni to'xtatish = so'rovni bekor qilish va rasmni o'chirish (serverda ham).
async function cancel(): Promise<void> {
    await deletePhoto();
}

async function deletePhoto(): Promise<void> {
    if (tryOn.current.value) await tryOn.remove(tryOn.current.value.id);
    if (photoUrl.value) URL.revokeObjectURL(photoUrl.value);
    photo.value = null;
    photoUrl.value = null;
    sourceRequestId.value = null;
    tryOn.current.value = null;
    step.value = 'setup';
}

function anotherLook(): void {
    step.value = 'setup';   // rasm saqlanadi (sourceRequestId), faqat look tanlanadi
}

function saveKey(): void {
    apiKey.save(keyDraft.value, rememberDraft.value);
}

const cost = computed(() => `$${props.estimateUsd[quality.value].toFixed(2)}`);
const errorFor = (field: string): string | null => tryOn.errors.value[field]?.[0] ?? null;
const lookSummary = computed(() => [look.value?.name, fabric.value?.name].filter(Boolean).join(' · '));
</script>

<template>
    <Head title="Kiyinib ko'ring" />

    <MotionConfig reduced-motion="user">
        <div class="min-h-screen bg-paper pb-28 font-body text-ink antialiased">
            <!-- TOP BAR -->
            <header class="bg-ink text-[#EDEFF3]">
                <div class="mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-4 px-5 py-4 md:px-10">
                    <Link href="/" class="font-display text-[22px] font-semibold">Boutique<span class="text-chalk">·</span>AI</Link>
                    <ol aria-label="Qadamlar" class="hidden gap-2 text-sm md:flex">
                        <li
                            v-for="(label, key) in { setup: '1 · Siz va look', waiting: '2 · Tikilmoqda', result: '3 · Natija' }"
                            :key="key"
                            :aria-current="step === key ? 'step' : undefined"
                            class="rounded-full px-3.5 py-2"
                            :class="step === key ? 'bg-chalk font-semibold text-ink' : 'text-fog'"
                        >
                            {{ label }}
                        </li>
                    </ol>
                    <div class="flex items-center gap-2.5 text-[13px] text-mist">
                        <template v-if="requiresApiKey && apiKey.isValidFormat.value">
                            <span class="size-2 rounded-full bg-[#6CC59B]" aria-hidden="true" />
                            <span class="font-mono">{{ apiKey.masked.value }}</span>
                            <button type="button" class="text-fog underline hover:text-chalk" @click="apiKey.forget()">o'chirish</button>
                        </template>
                        <span v-else-if="!requiresApiKey" class="rounded-full border border-white/20 px-3 py-1.5">Demo rejim · kalit kerak emas</span>
                    </div>
                </div>
            </header>

            <!-- XATO -->
            <div v-if="tryOn.generalError.value || tryOn.current.value?.status === 'failed'" role="alert" class="mx-auto mt-6 max-w-[1360px] px-5 md:px-10">
                <p class="rounded-2xl border border-[#A23C2C]/30 bg-[#FBEDEA] px-5 py-4 text-[15px] text-[#7A2A1E]">
                    {{ tryOn.generalError.value ?? tryOn.current.value?.error ?? 'Xato yuz berdi.' }}
                </p>
            </div>

            <AnimatePresence mode="wait">
                <!-- 1–2. TANLASH -->
                <motion.main
                    v-if="step === 'setup'"
                    key="setup"
                    class="mx-auto flex max-w-[1360px] flex-wrap items-start gap-8 px-5 py-8 md:px-10 md:py-10"
                    :initial="{ opacity: 0 }"
                    :animate="{ opacity: 1 }"
                    :exit="{ opacity: 0 }"
                >
                    <!-- Kalit -->
                    <section v-if="requiresApiKey && !keyReady" aria-labelledby="key-title" class="w-full rounded-[20px] bg-white p-6 md:p-8">
                        <form class="flex flex-col gap-4" @submit.prevent="saveKey">
                            <h1 id="key-title" class="font-display text-3xl font-medium">OpenAI API kalitingiz</h1>
                            <p class="max-w-[640px] text-[15px] leading-relaxed text-slate">
                                Generatsiya sizning kalitingiz bilan bajariladi (bitta look ≈ {{ `$${estimateUsd.medium.toFixed(2)}–${estimateUsd.high.toFixed(2)}` }}).
                                Kalit faqat shu brauzerda turadi va serverda saqlanmaydi. Maslahat: OpenAI'da alohida project oching va oylik limit qo'ying.
                            </p>
                            <div class="flex flex-wrap items-end gap-3">
                                <label class="flex min-w-[280px] flex-1 flex-col gap-1.5 text-sm text-slate">
                                    API kalit
                                    <input v-model="keyDraft" type="password" autocomplete="off" spellcheck="false" placeholder="sk-..." class="h-12 rounded-xl border border-line px-4 font-mono text-base text-ink" />
                                </label>
                                <button type="submit" class="h-12 rounded-full bg-ink px-6 font-semibold text-chalk disabled:opacity-40" :disabled="!/^sk-[A-Za-z0-9_-]{20,200}$/.test(keyDraft.trim())">
                                    Saqlash
                                </button>
                            </div>
                            <label class="flex items-center gap-2.5 text-sm text-[#3D4553]">
                                <input v-model="rememberDraft" type="checkbox" class="size-4 accent-ink" />
                                Shu qurilmada eslab qolish (aks holda tab yopilganda o'chadi)
                            </label>
                        </form>
                    </section>

                    <!-- Siz -->
                    <section aria-labelledby="you-title" class="flex max-w-[460px] flex-[1_1_380px] flex-col gap-5">
                        <h1 id="you-title" class="font-display text-[38px] font-medium">Siz</h1>
                        <PhotoDrop :preview-url="photoUrl" :error="errorFor('photo')" @select="selectPhoto" />
                        <div class="flex flex-col gap-4 rounded-[20px] bg-white p-5">
                            <HeightTape v-model="heightCm" />
                            <p v-if="errorFor('height_cm')" role="alert" class="text-sm text-[#A23C2C]">{{ errorFor('height_cm') }}</p>
                            <div class="flex flex-wrap gap-3">
                                <label class="flex flex-[1_1_140px] flex-col gap-1.5 text-sm text-slate">
                                    Bel (sm), ixtiyoriy
                                    <input v-model.number="waistCm" type="number" min="50" max="160" inputmode="numeric" placeholder="84" class="h-11 rounded-xl border border-line px-3.5 font-mono text-base text-ink" />
                                </label>
                                <div class="flex flex-[2_1_220px] flex-col gap-1.5">
                                    <span id="size-label" class="text-sm text-slate">Odatdagi razmer</span>
                                    <div role="radiogroup" aria-labelledby="size-label" class="flex flex-wrap gap-1.5">
                                        <button
                                            v-for="s in sizes"
                                            :key="s"
                                            type="button"
                                            role="radio"
                                            :aria-checked="size === s"
                                            class="h-11 min-w-11 rounded-xl border font-mono text-sm"
                                            :class="size === s ? 'border-ink bg-ink text-[#EDEFF3]' : 'border-line bg-white hover:border-ink'"
                                            @click="size = s"
                                        >
                                            {{ s }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <label class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed text-[#3D4553]">
                            <input v-model="consent" type="checkbox" class="mt-0.5 size-5 accent-ink" />
                            <span>Rasmim AI'ga (OpenAI) yuborilishiga va {{ requiresApiKey ? 'mening kalitimdan' : '' }} to'lanishiga roziman. Rasm 24 soatda o'chiriladi.</span>
                        </label>
                        <p v-if="errorFor('consent')" role="alert" class="-mt-3 text-sm text-[#A23C2C]">{{ errorFor('consent') }}</p>
                    </section>

                    <!-- Uslub va look -->
                    <section aria-labelledby="look-title" class="flex min-w-0 flex-[999_1_560px] flex-col gap-6">
                        <LookPicker v-model:style-id="styleId" v-model:look-id="lookId" :styles="styles" />
                        <div v-if="fabrics.length > 1" class="rounded-[20px] bg-white p-5">
                            <FabricSwatches v-model="fabricId" :fabrics="fabrics" />
                            <p v-if="errorFor('fabric_id')" role="alert" class="mt-2 text-sm text-[#A23C2C]">{{ errorFor('fabric_id') }}</p>
                        </div>
                    </section>
                </motion.main>

                <!-- 3. KUTISH -->
                <motion.main
                    v-else-if="step === 'waiting'"
                    key="waiting"
                    class="px-5 py-10"
                    :initial="{ opacity: 0, y: 16 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :exit="{ opacity: 0 }"
                >
                    <WaitingStage :photo-url="photoUrl" :status="tryOn.current.value?.status ?? 'queued'" :summary="lookSummary" @cancel="cancel" />
                </motion.main>

                <!-- 4. NATIJA -->
                <motion.main
                    v-else
                    key="result"
                    class="mx-auto flex max-w-[1360px] flex-wrap items-start gap-8 px-5 py-8 md:px-10"
                    :initial="{ opacity: 0 }"
                    :animate="{ opacity: 1 }"
                    :exit="{ opacity: 0 }"
                >
                    <section aria-label="Natija" class="min-w-0 flex-[999_1_560px]">
                        <BeforeAfter v-if="tryOn.current.value?.result_url" :before="photoUrl" :after="tryOn.current.value.result_url" />
                    </section>

                    <aside aria-labelledby="look-name" class="flex max-w-[460px] flex-[1_1_380px] flex-col gap-4">
                        <div class="flex flex-col gap-4 rounded-3xl bg-white p-6">
                            <div class="flex flex-col gap-1.5">
                                <span class="font-mono text-xs tracking-[0.12em] text-[#8A6A1E] uppercase">{{ lookStyle?.name }}</span>
                                <h1 id="look-name" class="font-display text-[40px] leading-tight font-medium">{{ look?.name }}</h1>
                            </div>
                            <ul class="flex flex-col">
                                <motion.li
                                    v-for="(p, i) in look?.products ?? []"
                                    :key="p.id"
                                    class="flex justify-between gap-3 border-b border-paper py-3 text-[15px] last:border-0"
                                    :initial="{ opacity: 0, x: 12 }"
                                    :animate="{ opacity: 1, x: 0 }"
                                    :transition="{ delay: 0.2 + i * 0.07 }"
                                >
                                    <span>
                                        <span class="text-slate">{{ p.type_label }} · </span>{{ p.name }}
                                        <span v-if="p.type === 'trousers' && fabric" class="text-slate"> · {{ fabric.name }}</span>
                                    </span>
                                    <span class="font-mono text-sm whitespace-nowrap">
                                        {{ uzs(p.price_uzs + (p.type === 'trousers' ? (fabric?.extra_price_uzs ?? 0) : 0)) }}
                                    </span>
                                </motion.li>
                            </ul>
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-[15px] text-slate">Look narxi</span>
                                <span class="font-mono text-2xl font-medium">{{ uzs(total) }} so'm</span>
                            </div>
                            <div v-if="tryOn.current.value?.suggested_size" class="flex items-center justify-between gap-3 rounded-2xl bg-chalk-soft px-4 py-3.5">
                                <span class="text-sm text-[#3D3013]">Taxminiy razmer<br /><span class="text-xs">bo'y {{ heightCm }} sm{{ waistCm ? ` · bel ${waistCm} sm` : '' }}</span></span>
                                <span class="font-mono text-3xl font-medium">{{ tryOn.current.value.suggested_size }}</span>
                            </div>
                            <p v-if="tryOn.current.value?.cost_usd" class="text-xs text-slate">Sarflandi: ${{ Number(tryOn.current.value.cost_usd).toFixed(4) }}</p>
                        </div>

                        <div v-if="fabrics.length > 1" class="flex flex-col gap-3.5 rounded-3xl bg-white p-6">
                            <FabricSwatches v-model="swapFabricId" :fabrics="fabrics" label="Shimni boshqa tkanda ko'rish" />
                            <button
                                v-if="swapFabricId !== fabricId"
                                type="button"
                                class="rounded-full bg-ink py-3.5 text-[15px] font-semibold text-chalk disabled:opacity-50"
                                :disabled="tryOn.submitting.value || !keyReady"
                                @click="generate(swapFabricId)"
                            >
                                Shu tkanda ko'rish · ≈ {{ cost }}
                            </button>
                            <p v-else class="text-sm text-slate">Rasm qayta yuklanmaydi — faqat shim yangi tkanda tikiladi.</p>
                        </div>

                        <div class="flex flex-wrap gap-2.5">
                            <a
                                v-if="tryOn.current.value?.result_url"
                                :href="tryOn.current.value.result_url"
                                download="boutique-ai-natija.png"
                                class="flex h-12 flex-[1_1_140px] items-center justify-center rounded-full bg-ink text-[15px] font-semibold text-[#EDEFF3]"
                            >
                                Yuklab olish
                            </a>
                            <button type="button" class="h-12 flex-[1_1_140px] rounded-full border border-[#C2C8D2] text-[15px] hover:border-ink" @click="anotherLook">
                                Boshqa look
                            </button>
                        </div>
                        <button type="button" class="self-start py-1.5 text-sm text-[#A23C2C] underline" @click="deletePhoto">Rasmimni hozir o'chirish</button>
                    </aside>
                </motion.main>
            </AnimatePresence>

            <!-- PASTKI PANEL (faqat tanlash bosqichida) -->
            <div v-if="step === 'setup'" class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-white/95 backdrop-blur">
                <div class="mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-4 px-5 py-3.5 md:px-10">
                    <div class="flex flex-wrap gap-6 text-sm text-slate">
                        <span>Look <strong class="ml-1.5 font-mono text-lg text-ink">{{ uzs(total) }} so'm</strong></span>
                        <span v-if="size" class="hidden sm:inline">Razmer <strong class="ml-1.5 font-mono text-lg text-ink">{{ size }}</strong></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3.5">
                        <div role="radiogroup" aria-label="Sifat" class="flex rounded-full bg-paper p-1">
                            <button
                                v-for="q in (['medium', 'high'] as const)"
                                :key="q"
                                type="button"
                                role="radio"
                                :aria-checked="quality === q"
                                class="h-9 rounded-full px-3.5 text-sm capitalize"
                                :class="quality === q ? 'bg-ink text-[#EDEFF3]' : 'text-[#3D4553]'"
                                @click="quality = q"
                            >
                                {{ q }}
                            </button>
                        </div>
                        <span class="font-mono text-sm text-[#3D4553]">≈ {{ cost }}</span>
                        <button
                            type="button"
                            class="rounded-full bg-ink px-6 py-3.5 text-base font-semibold text-chalk transition-opacity disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!canSubmit"
                            @click="generate()"
                        >
                            {{ tryOn.submitting.value ? 'Yuborilmoqda…' : 'Kiyintirish →' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MotionConfig>
</template>
