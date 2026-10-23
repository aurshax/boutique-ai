<script setup lang="ts">
// Bottom bar (setup step): look price, size, quality, estimated cost and "Dress me".
import { QUALITY_OPTIONS } from '@/entities/try-on-request';
import { useI18n } from '@/shared/i18n';
import { formatUzs } from '@/shared/lib/format';
import { Button } from '@/shared/ui/button';
import { injectTryOnFlow } from '../model/useTryOnFlow';

const { t } = useI18n();
const { form, canSubmit, submitting, estimate, usesRealAi, generate } =
    injectTryOnFlow();
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
                    >{{ t('tryOn.submit.look') }}
                    <strong class="ml-1.5 font-mono text-lg text-ink">{{
                        t('common.uzs', { amount: formatUzs(total) })
                    }}</strong></span
                >
                <span v-if="size" class="hidden sm:inline"
                    >{{ t('tryOn.submit.size') }}
                    <strong class="ml-1.5 font-mono text-lg text-ink">{{
                        size
                    }}</strong></span
                >
            </div>
            <div class="flex flex-wrap items-center gap-3.5">
                <div
                    v-if="usesRealAi"
                    role="radiogroup"
                    :aria-label="t('tryOn.submit.quality')"
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
                        {{ t(option.labelKey) }}
                    </button>
                </div>
                <span class="font-mono text-sm text-[#3D4553]">{{
                    usesRealAi
                        ? t('tryOn.submit.estimate', { estimate })
                        : t('tryOn.submit.demo')
                }}</span>
                <Button
                    variant="brand"
                    size="pill"
                    class="h-[52px] text-base"
                    :disabled="!canSubmit"
                    @click="generate"
                >
                    {{
                        submitting
                            ? t('tryOn.submit.submitting')
                            : t('tryOn.submit.submit')
                    }}
                </Button>
            </div>
        </div>
    </div>
</template>
