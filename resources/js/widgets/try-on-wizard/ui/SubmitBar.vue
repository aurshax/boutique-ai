<script setup lang="ts">
// Pastki panel (1-qadamda): look narxi, razmer, sifat, taxminiy xarajat va "Kiyintirish".
import { QUALITY_OPTIONS } from '@/entities/try-on-request';
import { formatUzs } from '@/shared/lib/format';
import { Button } from '@/shared/ui/button';
import { injectTryOnFlow } from '../model/useTryOnFlow';

const { form, canSubmit, submitting, estimate, generate } = injectTryOnFlow();
const { total, size, quality } = form;
</script>

<template>
    <div
        class="fixed inset-x-0 bottom-0 z-20 border-t border-line bg-white/95 backdrop-blur"
    >
        <div
            class="mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-4 px-5 py-3.5 md:px-10"
        >
            <div class="flex flex-wrap gap-6 text-sm text-slate">
                <span
                    >Look
                    <strong class="ml-1.5 font-mono text-lg text-ink"
                        >{{ formatUzs(total) }} so'm</strong
                    ></span
                >
                <span v-if="size" class="hidden sm:inline"
                    >Razmer
                    <strong class="ml-1.5 font-mono text-lg text-ink">{{
                        size
                    }}</strong></span
                >
            </div>
            <div class="flex flex-wrap items-center gap-3.5">
                <div
                    role="radiogroup"
                    aria-label="Sifat"
                    class="flex rounded-full bg-paper p-1"
                >
                    <button
                        v-for="option in QUALITY_OPTIONS"
                        :key="option.value"
                        type="button"
                        role="radio"
                        :aria-checked="quality === option.value"
                        class="h-9 rounded-full px-3.5 text-sm"
                        :class="
                            quality === option.value
                                ? 'bg-ink text-[#EDEFF3]'
                                : 'text-[#3D4553]'
                        "
                        @click="quality = option.value"
                    >
                        {{ option.label }}
                    </button>
                </div>
                <span class="font-mono text-sm text-[#3D4553]"
                    >≈ {{ estimate }}</span
                >
                <Button
                    variant="brand"
                    size="pill"
                    class="h-[52px] text-base"
                    :disabled="!canSubmit"
                    @click="generate"
                >
                    {{ submitting ? 'Yuborilmoqda…' : 'Kiyintirish →' }}
                </Button>
            </div>
        </div>
    </div>
</template>
