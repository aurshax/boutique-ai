<script setup lang="ts">
/**
 * Photo picker: click or drag & drop. Browser pre-check limits come from the backend (props.limits);
 * the real check and sanitising happen on the server (ImageService). A scan line sweeps over a new photo.
 */
import { motion } from 'motion-v';
import { computed, ref } from 'vue';
import { useI18n } from '@/shared/i18n';
import InputError from '@/shared/ui/InputError.vue';
import type { UploadLimits } from '../model/types';
import {
    acceptAttribute,
    checkUpload,
    describeAllowed,
    maxMegabytes,
} from '../model/upload';

const props = defineProps<{
    previewUrl: string | null;
    limits: UploadLimits;
    error?: string | null;
}>();
const emit = defineEmits<{ select: [file: File] }>();

const { t } = useI18n();
const input = ref<HTMLInputElement | null>(null);
const dragOver = ref(false);
const localError = ref<string | null>(null);
const accept = computed(() => acceptAttribute(props.limits));
const hint = computed(() =>
    t('tryOn.photo.hint', {
        formats: describeAllowed(props.limits, t('tryOn.photo.or')),
        mb: maxMegabytes(props.limits),
    }),
);

function take(file: File | undefined): void {
    localError.value = null;
    if (!file) return;
    const check = checkUpload(file, props.limits, t('tryOn.photo.or'));
    if (!check.ok) {
        localError.value = t(check.messageKey, check.params);
        return;
    }
    emit('select', file);
}

function onDrop(event: DragEvent): void {
    dragOver.value = false;
    take(event.dataTransfer?.files?.[0]);
}

function onChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    take(target.files?.[0]);
    target.value = ''; // so picking the same file again still fires `change`
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            class="relative aspect-[4/5] overflow-hidden rounded-[20px] bg-[#DDE1E7] transition-shadow"
            :class="dragOver ? 'ring-2 ring-chalk-deep ring-offset-2' : ''"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="onDrop"
        >
            <template v-if="previewUrl">
                <img
                    :src="previewUrl"
                    :alt="t('tryOn.photo.alt')"
                    class="h-full w-full object-cover"
                />
                <motion.div
                    :key="previewUrl"
                    aria-hidden="true"
                    class="absolute inset-x-0 h-24 bg-[linear-gradient(180deg,rgba(229,196,107,0)_0%,rgba(229,196,107,0.55)_48%,rgba(229,196,107,0)_100%)]"
                    :initial="{ top: '-15%' }"
                    :animate="{ top: '105%' }"
                    :transition="{ duration: 0.9, ease: 'easeInOut' }"
                />
                <div
                    class="absolute inset-x-3.5 bottom-3.5 flex flex-wrap justify-between gap-2"
                >
                    <span
                        class="rounded-full bg-ink/80 px-3 py-2 text-[13px] text-[#EDEFF3]"
                        >{{ t('tryOn.photo.privacy') }}</span
                    >
                    <button
                        type="button"
                        class="rounded-full bg-white px-3.5 py-2 text-[13px] font-semibold text-ink"
                        @click="input?.click()"
                    >
                        {{ t('tryOn.photo.replace') }}
                    </button>
                </div>
            </template>

            <button
                v-else
                type="button"
                class="flex h-full w-full flex-col items-center justify-center gap-3 border-2 border-dashed border-[#B8C0CC] p-8 text-center text-slate hover:border-ink hover:text-ink"
                @click="input?.click()"
            >
                <svg
                    aria-hidden="true"
                    width="36"
                    height="36"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 16V4M7 9l5-5 5 5" />
                    <path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
                </svg>
                <span class="text-base font-semibold text-ink">{{
                    t('tryOn.photo.empty')
                }}</span>
                <span class="max-w-[260px] text-sm">{{ hint }}</span>
            </button>

            <input
                ref="input"
                type="file"
                :accept="accept"
                class="sr-only"
                :aria-label="t('tryOn.photo.inputLabel')"
                @change="onChange"
            />
        </div>
        <InputError :message="localError ?? error" />
    </div>
</template>
