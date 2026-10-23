<script setup lang="ts">
// Wizard header: logo, steps and key status.
import { ApiKeyBadge } from '@/features/byok-api-key';
import type { KeyMode } from '@/features/byok-api-key';
import { useI18n } from '@/shared/i18n';
import BrandLogo from '@/shared/ui/BrandLogo.vue';
import { STEP_LABEL_KEYS } from '../model/flow';
import type { Step } from '../model/flow';

defineProps<{ step: Step; keyMode: KeyMode }>();

const { t } = useI18n();
const steps = Object.entries(STEP_LABEL_KEYS) as [Step, string][];
</script>

<template>
    <header class="bg-ink text-[#EDEFF3]">
        <div
            class="mx-auto flex max-w-[1360px] flex-wrap items-center justify-between gap-4 px-5 py-4 md:px-10"
        >
            <BrandLogo size="sm" />
            <ol
                :aria-label="t('tryOn.steps.label')"
                class="hidden gap-2 text-sm md:flex"
            >
                <li
                    v-for="[key, label] in steps"
                    :key="key"
                    :aria-current="step === key ? 'step' : undefined"
                    class="rounded-full px-3.5 py-2"
                    :class="
                        step === key
                            ? 'bg-chalk font-semibold text-ink'
                            : 'text-fog'
                    "
                >
                    {{ t(label) }}
                </li>
            </ol>
            <ApiKeyBadge :mode="keyMode" />
        </div>
    </header>
</template>
