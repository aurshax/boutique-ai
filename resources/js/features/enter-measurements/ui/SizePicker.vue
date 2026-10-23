<script setup lang="ts">
// Odatdagi razmer: radiogroup. Ro'yxat backend'dan (CreateTryOnRequest::SIZES).
import { useId } from 'vue';

defineProps<{ sizes: string[]; label?: string }>();
const model = defineModel<string | null>({ required: true });
const labelId = useId();
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <span :id="labelId" class="text-sm text-slate">{{
            label ?? 'Odatdagi razmer'
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
