<script setup lang="ts">
// Header'dagi kalit holati: maskalangan kalit + "o'chirish", yoki demo rejim belgisi.
import { useApiKey } from '../model/useApiKey';

defineProps<{ required: boolean }>();

const { isValid, masked, forget } = useApiKey();
</script>

<template>
    <div class="flex items-center gap-2.5 text-[13px] text-mist">
        <template v-if="required && isValid">
            <span class="size-2 rounded-full bg-[#6CC59B]" aria-hidden="true" />
            <span class="font-mono">{{ masked }}</span>
            <button
                type="button"
                class="text-fog underline hover:text-chalk"
                @click="forget"
            >
                o'chirish
            </button>
        </template>
        <span
            v-else-if="!required"
            class="rounded-full border border-white/20 px-3 py-1.5"
            >Demo rejim · kalit kerak emas</span
        >
    </div>
</template>
