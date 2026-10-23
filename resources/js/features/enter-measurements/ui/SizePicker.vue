<script setup lang="ts">
// Usual size: a radiogroup. The list comes from the backend (CreateTryOnRequest::SIZES).
import { useId } from 'vue';
import { useI18n } from '@/shared/i18n';

defineProps<{ sizes: string[]; label?: string }>();
const model = defineModel<string | null>({ required: true });
const { t } = useI18n();
const labelId = useId();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <span :id="labelId" class="text-sm text-slate">{{
            label ?? t('tryOn.size.label')
        }}</span>
        <div
            role="radiogroup"
            :aria-labelledby="labelId"
            class="flex flex-wrap gap-1.5"
        >
            <button
                v-for="s in sizes"
                :key="s"
                type="button"
                role="radio"
                :aria-checked="model === s"
                class="h-11 min-w-11 rounded-xl border font-mono text-sm transition-colors"
                :class="
                    model === s
                        ? 'border-ink bg-ink text-[#EDEFF3]'
                        : 'border-line bg-white hover:border-ink'
                "
                @click="model = s"
            >
                {{ s }}
            </button>
        </div>
    </div>
</template>
