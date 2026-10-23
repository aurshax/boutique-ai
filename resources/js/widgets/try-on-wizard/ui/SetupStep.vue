<script setup lang="ts">
// Step 1: API key (real-AI modes), photo, height, waist, size, consent, look and fabric.
// Container: state from injectTryOnFlow(), presentation from features/* components.
import { computed } from 'vue';
import { fabricScope } from '@/entities/catalog';
import { ApiKeyForm } from '@/features/byok-api-key';
import { HeightTape, SizePicker } from '@/features/enter-measurements';
import { FabricSwatches } from '@/features/select-fabric';
import { LookPicker } from '@/features/select-look';
import { PhotoDrop } from '@/features/upload-photo';
import { useI18n } from '@/shared/i18n';
import InputError from '@/shared/ui/InputError.vue';
import { injectTryOnFlow } from '../model/useTryOnFlow';

const { t } = useI18n();
const { page, form, apiKey, usesRealAi, fieldError } = injectTryOnFlow();
// Top-level refs — auto-unwrapped in the template and usable with v-model.
const {
    photoUrl,
    heightCm,
    waistCm,
    size,
    styleId,
    lookId,
    fabricId,
    consent,
    fabrics,
    look,
    setPhoto,
} = form;
const scope = computed(() => fabricScope(look.value));
const { remembered, isValid: keyValid, save: saveKey } = apiKey;
</script>

<template>
    <div
        class="mx-auto flex max-w-[1360px] flex-wrap items-start gap-8 px-5 py-8 md:px-10 md:py-10"
    >
        <ApiKeyForm
            v-if="page.keyMode !== 'none' && !keyValid"
            :mode="page.keyMode"
            :estimate="page.estimateUsd"
            :initial-remember="remembered"
            @save="saveKey"
        />

        <section
            aria-labelledby="you-title"
            class="flex max-w-[460px] flex-[1_1_380px] flex-col gap-5"
        >
            <h1 id="you-title" class="font-display text-[38px] font-medium">
                {{ t('tryOn.you.title') }}
            </h1>
            <PhotoDrop
                :preview-url="photoUrl"
                :limits="page.upload"
                :error="fieldError('photo')"
                @select="setPhoto"
            />

            <div class="flex flex-col gap-4 rounded-[20px] bg-white p-5">
                <HeightTape v-model="heightCm" />
                <InputError :message="fieldError('height_cm')" />
                <div class="flex flex-wrap gap-3">
                    <label
                        class="flex flex-[1_1_140px] flex-col gap-1.5 text-sm text-slate"
                    >
                        {{ t('tryOn.you.waist') }}
                        <input
                            v-model.number="waistCm"
                            type="number"
                            min="50"
                            max="160"
                            inputmode="numeric"
                            placeholder="84"
                            class="h-11 rounded-xl border border-line px-3.5 font-mono text-base text-ink"
                        />
                    </label>
                    <SizePicker
                        v-model="size"
                        :sizes="page.sizes"
                        class="flex-[2_1_220px]"
                    />
                </div>
                <InputError
                    :message="fieldError('waist_cm') ?? fieldError('size')"
                />
            </div>

            <label
                class="flex cursor-pointer items-start gap-3 text-sm leading-relaxed text-[#3D4553]"
            >
                <input
                    v-model="consent"
                    type="checkbox"
                    class="mt-0.5 size-5 accent-ink"
                />
                <span>{{
                    usesRealAi
                        ? t('tryOn.you.consentAi')
                        : t('tryOn.you.consentDemo')
                }}</span>
            </label>
            <InputError :message="fieldError('consent')" class="-mt-3" />
        </section>

        <section
            aria-labelledby="look-title"
            class="flex min-w-0 flex-[999_1_560px] flex-col gap-6"
        >
            <LookPicker
                v-model:style-id="styleId"
                v-model:look-id="lookId"
                :styles="page.styles"
            />
            <div
                v-if="fabrics.length > 1"
                class="flex flex-col gap-2 rounded-[20px] bg-white p-5"
            >
                <FabricSwatches
                    v-model="fabricId"
                    :fabrics="fabrics"
                    :label="t(scope.labelKey)"
                />
                <InputError :message="fieldError('fabric_id')" />
            </div>
        </section>
    </div>
</template>
