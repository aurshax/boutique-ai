<script setup lang="ts">
import { useI18n } from '@/shared/i18n';
import Reveal from '@/shared/ui/Reveal.vue';
import { HOW_STEP_NUMBERS } from '../model/content';

const { t } = useI18n();
</script>

<template>
    <section
        id="how"
        class="mx-auto flex max-w-[1360px] scroll-mt-8 flex-col gap-12 px-5 pt-24 pb-10 md:px-10 md:pt-[120px]"
    >
        <Reveal class="flex flex-wrap items-end justify-between gap-8">
            <h2
                class="max-w-[620px] font-display text-4xl leading-[1.05] font-medium text-balance md:text-[52px]"
            >
                {{ t('home.how.title') }}
            </h2>
            <p class="max-w-[420px] text-[17px] leading-relaxed text-fog">
                {{ t('home.how.lead') }}
            </p>
        </Reveal>
        <ol class="grid gap-6 md:grid-cols-3">
            <Reveal
                v-for="(no, i) in HOW_STEP_NUMBERS"
                :key="no"
                as="li"
                :delay="i * 0.12"
                class="flex min-h-[240px] flex-col gap-4 rounded-[18px] border-[1.5px] border-dashed border-chalk/55 p-8"
            >
                <span class="font-mono text-sm text-chalk">{{ no }}</span>
                <h3 class="text-2xl font-semibold">
                    {{ t(`home.how.steps.${i}.title`) }}
                </h3>
                <p class="text-base leading-relaxed text-fog">
                    {{ t(`home.how.steps.${i}.text`) }}
                </p>
            </Reveal>
        </ol>
    </section>
</template>
