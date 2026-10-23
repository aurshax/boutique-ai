<script setup lang="ts">
// Fabric picker (radiogroup). Swatch look comes from entities/catalog swatchStyle().
import { computed, useId } from 'vue';
import { swatchStyle } from '@/entities/catalog';
import type { Fabric } from '@/entities/catalog';
import { useI18n } from '@/shared/i18n';
import { formatShortSurcharge, formatUzs } from '@/shared/lib/format';

const props = defineProps<{ fabrics: Fabric[]; label?: string }>();
const model = defineModel<number | null>({ required: true });

const { t } = useI18n();
const labelId = useId();
const selected = computed(
    () => props.fabrics.find((f) => f.id === model.value) ?? null,
);
</script>

<template>
    <div class="flex flex-col gap-3.5">
        <div class="flex flex-wrap justify-between gap-3">
            <span :id="labelId" class="text-base font-semibold">{{
                label ?? t('tryOn.fabric.trousers')
            }}</span>
            <span v-if="selected" class="text-sm text-slate">
                {{ selected.name }} ·
                {{
                    selected.extra_price_uzs
                        ? t('tryOn.fabric.surcharge', {
                              amount: formatUzs(selected.extra_price_uzs),
                          })
                        : t('tryOn.fabric.noSurcharge')
                }}
            </span>
        </div>
        <div
            role="radiogroup"
            :aria-labelledby="labelId"
            class="flex flex-wrap gap-3"
        >
            <button
                v-for="f in fabrics"
                :key="f.id"
                type="button"
                role="radio"
                :aria-checked="f.id === model"
                :aria-label="`${f.name}, ${f.composition}`"
                :title="f.name"
                class="flex flex-col items-center gap-1.5"
                @click="model = f.id"
            >
                <span
                    class="block size-14 rounded-[14px] transition-transform hover:scale-105"
                    :class="
                        f.id === model
                            ? 'ring-[3px] ring-chalk-deep ring-offset-2'
                            : 'ring-1 ring-line'
                    "
                    :style="swatchStyle(f)"
                />
                <span class="font-mono text-[11px] text-slate">{{
                    formatShortSurcharge(f.extra_price_uzs)
                }}</span>
            </button>
        </div>
    </div>
</template>
