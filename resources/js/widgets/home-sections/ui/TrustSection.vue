<script setup lang="ts">
import { useI18n } from '@/shared/i18n';
import Reveal from '@/shared/ui/Reveal.vue';
import { TRUST_ICONS } from '../model/content';

const { t } = useI18n();
</script>

<template>
    <section
        id="trust"
        class="mx-auto max-w-[1360px] scroll-mt-8 px-5 pt-24 pb-10 md:px-10"
    >
        <Reveal
            class="grid gap-10 rounded-3xl bg-ink-2 p-8 md:grid-cols-3 md:p-14"
        >
            <div
                v-for="(icon, i) in TRUST_ICONS"
                :key="i"
                class="flex flex-col gap-3"
            >
                <component
                    :is="icon"
                    aria-hidden="true"
                    :size="28"
                    :stroke-width="1.6"
                    class="text-chalk"
                />
                <h3 class="text-xl font-semibold">
                    {{ t(`home.trust.items.${i}.title`) }}
                </h3>
                <p class="text-[15px] leading-relaxed text-fog">
                    {{ t(`home.trust.items.${i}.text`) }}
                </p>
            </div>
        </Reveal>
    </section>
</template>
