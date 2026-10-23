<script setup lang="ts">
// Key status in the header: the masked key with "remove", or a demo-mode badge.
import { useI18n } from '@/shared/i18n';
import { useApiKey } from '../model/useApiKey';
import type { KeyMode } from '../model/types';

defineProps<{ mode: KeyMode }>();

const { t } = useI18n();
const { isValid, masked, forget } = useApiKey();
</script>

<template>
    <div class="flex items-center gap-2.5 text-[13px] text-mist">
        <template v-if="mode !== 'none' && isValid">
            <span class="size-2 rounded-full bg-[#6CC59B]" aria-hidden="true" />
            <span class="font-mono">{{ masked }}</span>
            <button
                type="button"
                class="text-fog underline hover:text-chalk"
                @click="forget"
            >
                {{ t('tryOn.apiKey.badgeForget') }}
            </button>
        </template>
        <span
            v-else-if="mode !== 'required'"
            class="rounded-full border border-white/20 px-3 py-1.5"
            >{{ t('tryOn.apiKey.badgeDemo') }}</span
        >
    </div>
</template>
