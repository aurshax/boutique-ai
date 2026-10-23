<script setup lang="ts">
/**
 * Rasm tanlash: bosish yoki sudrab tashlash. Brauzerda dastlabki tekshiruv (tur, hajm),
 * haqiqiy tekshiruv va tozalash serverda (ImageService). Yangi rasmda skaner chizig'i o'tadi.
 */
import { motion } from 'motion-v';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{ error?: string | null; previewUrl: string | null }>();
const emit = defineEmits<{ select: [file: File] }>();

const ACCEPT = ['image/jpeg', 'image/png', 'image/webp'];
const MAX_BYTES = 10 * 1024 * 1024;

const input = ref<HTMLInputElement | null>(null);
const over = ref(false);
const localError = ref<string | null>(null);
const scanKey = ref(0);

function take(file: File | undefined): void {
    localError.value = null;
    if (!file) return;
    if (!ACCEPT.includes(file.type)) {
        localError.value = 'Faqat JPEG, PNG yoki WebP rasm.';
        return;
    }
    if (file.size > MAX_BYTES) {
        localError.value = 'Rasm 10 MB dan oshmasligi kerak.';
        return;
    }
    emit('select', file);
}

function onDrop(event: DragEvent): void {
    over.value = false;
    take(event.dataTransfer?.files?.[0]);
}

watch(() => props.previewUrl, (url) => {
    if (url) scanKey.value++;
});

onBeforeUnmount(() => {
    if (input.value) input.value.value = '';
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            class="relative aspect-[4/5] overflow-hidden rounded-[20px] bg-[#DDE1E7] transition-shadow"
            :class="over ? 'ring-2 ring-chalk-deep ring-offset-2' : ''"
            @dragover.prevent="over = true"
            @dragleave="over = false"
            @drop.prevent="onDrop"
        >
            <template v-if="previewUrl">
                <img :src="previewUrl" alt="Yuklangan rasmingiz" class="h-full w-full object-cover" />
                <motion.div
                    :key="scanKey"
                    aria-hidden="true"
                    class="absolute inset-x-0 h-24 bg-[linear-gradient(180deg,rgba(229,196,107,0)_0%,rgba(229,196,107,0.55)_48%,rgba(229,196,107,0)_100%)]"
                    :initial="{ top: '-15%' }"
                    :animate="{ top: '105%' }"
                    :transition="{ duration: 0.9, ease: 'easeInOut' }"
                />
                <div class="absolute inset-x-3.5 bottom-3.5 flex flex-wrap justify-between gap-2">
                    <span class="rounded-full bg-ink/80 px-3 py-2 text-[13px] text-[#EDEFF3]">Yuborishda GPS va metama'lumot o'chiriladi</span>
                    <button type="button" class="rounded-full bg-white px-3.5 py-2 text-[13px] font-semibold text-ink" @click="input?.click()">
                        Boshqa rasm
                    </button>
                </div>
            </template>

            <button
                v-else
                type="button"
                class="flex h-full w-full flex-col items-center justify-center gap-3 border-2 border-dashed border-[#B8C0CC] p-8 text-center text-slate hover:border-ink hover:text-ink"
                @click="input?.click()"
            >
                <svg aria-hidden="true" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 16V4M7 9l5-5 5 5" /><path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
                </svg>
                <span class="text-base font-semibold text-ink">Rasm yuklang yoki shu yerga tashlang</span>
                <span class="max-w-[260px] text-sm">To'liq bo'yli, old tomondan, yorug' joyda. JPEG, PNG yoki WebP, 10 MB gacha.</span>
            </button>

            <input
                ref="input"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                aria-label="Rasm tanlash"
                @change="take(($event.target as HTMLInputElement).files?.[0])"
            />
        </div>
        <p v-if="localError || error" role="alert" class="text-sm text-[#A23C2C]">{{ localError || error }}</p>
    </div>
</template>
