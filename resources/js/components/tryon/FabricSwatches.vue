<script setup lang="ts">
// Shim tkanlari: rasm bo'lsa — tekstura, bo'lmasa — asosiy rang.
import type { Fabric } from '@/types/tryon';

defineProps<{ fabrics: Fabric[]; label?: string }>();
const model = defineModel<number | null>({ required: true });

const uzs = (n: number): string => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const bg = (f: Fabric): Record<string, string> =>
    f.swatch_image
        ? { backgroundImage: `url(/${f.swatch_image.replace(/^\//, '')})`, backgroundSize: 'cover', backgroundPosition: 'center' }
        : { backgroundColor: f.color_hex };
</script>

<template>
    <div class="flex flex-col gap-3.5">
        <div class="flex flex-wrap justify-between gap-3">
            <span id="fabric-label" class="text-base font-semibold">{{ label ?? 'Shim tkani' }}</span>
            <span class="text-sm text-slate">
                <template v-for="f in fabrics" :key="f.id">
                    <template v-if="f.id === model">
                        {{ f.name }} · {{ f.extra_price_uzs ? `+${uzs(f.extra_price_uzs)} so'm` : "narx o'zgarmaydi" }}
                    </template>
                </template>
            </span>
        </div>
        <div role="radiogroup" aria-labelledby="fabric-label" class="flex flex-wrap gap-3">
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
                    :class="f.id === model ? 'ring-[3px] ring-chalk-deep ring-offset-2' : 'ring-1 ring-line'"
                    :style="bg(f)"
                />
                <span class="font-mono text-[11px] text-slate">{{ f.extra_price_uzs ? `+${f.extra_price_uzs / 1000}k` : '0' }}</span>
            </button>
        </div>
    </div>
</template>
