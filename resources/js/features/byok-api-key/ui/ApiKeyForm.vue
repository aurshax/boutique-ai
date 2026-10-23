<script setup lang="ts">
// BYOK: the visitor enters their own OpenAI key. It is kept in the browser only (useApiKey).
// mode 'required' — the full form; mode 'optional' (demo) — a short note that expands into the form.
import { computed, ref, useId } from 'vue';
import { useI18n } from '@/shared/i18n';
import { formatUsdRange } from '@/shared/lib/format';
import { Button } from '@/shared/ui/button';
import { isValidApiKey } from '../model/apiKey';
import type { KeyMode } from '../model/types';

const props = defineProps<{
    mode: Exclude<KeyMode, 'none'>;
    estimate: { medium: number; high: number };
    initialRemember: boolean;
}>();
const emit = defineEmits<{ save: [key: string, remember: boolean] }>();

const { t } = useI18n();
const titleId = useId();
const draft = ref('');
const remember = ref(props.initialRemember);
const expanded = ref(props.mode === 'required');
const valid = computed(() => isValidApiKey(draft.value));
const estimateText = computed(() =>
    formatUsdRange(props.estimate.medium, props.estimate.high),
);
</script>

<template>
    <section
        :aria-labelledby="titleId"
        class="w-full rounded-[20px] bg-white p-6 md:p-8"
    >
        <div class="flex flex-col gap-4">
            <h2 :id="titleId" class="font-display text-3xl font-medium">
                {{
                    mode === 'required'
                        ? t('tryOn.apiKey.title')
                        : t('tryOn.apiKey.demoTitle')
                }}
            </h2>
            <p class="max-w-[640px] text-[15px] leading-relaxed text-slate">
                {{
                    mode === 'required'
                        ? t('tryOn.apiKey.lead', { estimate: estimateText })
                        : t('tryOn.apiKey.demoLead', { estimate: estimateText })
                }}
            </p>

            <Button
                v-if="!expanded"
                variant="pill-outline"
                size="pill"
                class="self-start"
                @click="expanded = true"
            >
                {{ t('tryOn.apiKey.addKey') }}
            </Button>

            <form
                v-else
                class="flex flex-col gap-4"
                @submit.prevent="valid && emit('save', draft, remember)"
            >
                <div class="flex flex-wrap items-end gap-3">
                    <label
                        class="flex min-w-[280px] flex-1 flex-col gap-1.5 text-sm text-slate"
                    >
                        {{ t('tryOn.apiKey.label') }}
                        <input
                            v-model="draft"
                            type="password"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="sk-..."
                            :aria-invalid="draft !== '' && !valid"
                            class="h-12 rounded-xl border border-line px-4 font-mono text-base text-ink aria-invalid:border-[#A23C2C]"
                        />
                    </label>
                    <Button
                        type="submit"
                        variant="brand"
                        size="pill"
                        :disabled="!valid"
                        >{{ t('tryOn.apiKey.save') }}</Button
                    >
                </div>
                <label class="flex items-center gap-2.5 text-sm text-[#3D4553]">
                    <input
                        v-model="remember"
                        type="checkbox"
                        class="size-4 accent-ink"
                    />
                    {{ t('tryOn.apiKey.remember') }}
                </label>
            </form>
        </div>
    </section>
</template>
