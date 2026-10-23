<script setup lang="ts">
/**
 * Bo'y tanlash: santimetr lentasi. Sudrab o'zgartiriladi (1 sm = 8 px), +/− tugmalari va
 * klaviatura (← → PageUp PageDown Home End). role="slider" — ekran o'quvchilar uchun.
 * Qo'yib yuborilganda lenta "spring" bilan qadamga o'tiradi (CSS transition, overshoot).
 */
import { computed, ref } from 'vue';

const props = withDefaults(defineProps<{ min?: number; max?: number }>(), { min: 120, max: 230 });
const model = defineModel<number>({ required: true });

const PX_PER_CM = 8;
const dragging = ref(false);
let startX = 0;
let startValue = 0;

const clamp = (v: number): number => Math.min(props.max, Math.max(props.min, Math.round(v)));
const offset = computed(() => -(model.value - props.min) * PX_PER_CM);
const labels = computed(() => {
    const out: number[] = [];
    for (let cm = Math.ceil(props.min / 5) * 5; cm <= props.max; cm += 5) out.push(cm);
    return out;
});

function onPointerDown(event: PointerEvent): void {
    dragging.value = true;
    startX = event.clientX;
    startValue = model.value;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}
function onPointerMove(event: PointerEvent): void {
    if (!dragging.value) return;
    model.value = clamp(startValue - (event.clientX - startX) / PX_PER_CM);
}
function onPointerUp(): void {
    dragging.value = false;
}
function onKeydown(event: KeyboardEvent): void {
    const step: Record<string, number> = { ArrowLeft: -1, ArrowDown: -1, ArrowRight: 1, ArrowUp: 1, PageDown: -5, PageUp: 5 };
    if (event.key in step) {
        model.value = clamp(model.value + step[event.key]);
    } else if (event.key === 'Home') {
        model.value = props.min;
    } else if (event.key === 'End') {
        model.value = props.max;
    } else {
        return;
    }
    event.preventDefault();
}
</script>

<template>
    <div class="flex flex-col gap-3.5">
        <div class="flex items-center justify-between gap-3">
            <span id="height-label" class="text-base font-semibold">Bo'y</span>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    aria-label="Bo'yni 1 sm kamaytirish"
                    class="size-11 rounded-full border border-line bg-white text-xl hover:border-ink"
                    @click="model = clamp(model - 1)"
                >
                    −
                </button>
                <output for="height-tape" class="min-w-[120px] text-center font-mono text-[28px] font-medium tabular-nums">{{ model }} sm</output>
                <button
                    type="button"
                    aria-label="Bo'yni 1 sm oshirish"
                    class="size-11 rounded-full border border-line bg-white text-xl hover:border-ink"
                    @click="model = clamp(model + 1)"
                >
                    +
                </button>
            </div>
        </div>

        <div
            id="height-tape"
            role="slider"
            tabindex="0"
            aria-labelledby="height-label"
            :aria-valuemin="min"
            :aria-valuemax="max"
            :aria-valuenow="model"
            :aria-valuetext="`${model} santimetr`"
            class="relative h-12 cursor-grab touch-none overflow-hidden rounded-lg bg-chalk-soft select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink active:cursor-grabbing"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
            @keydown="onKeydown"
        >
            <!-- lenta: markaz chizig'i ostida joriy qiymat turadi -->
            <div
                class="absolute inset-y-0 left-1/2 text-ink"
                :class="dragging ? '' : 'transition-transform duration-300 ease-[cubic-bezier(.34,1.56,.64,1)]'"
                :style="{ transform: `translateX(${offset}px)`, width: `${(max - min) * 8 + 1}px` }"
                aria-hidden="true"
            >
                <div class="ruler absolute inset-x-0 bottom-0 h-[22px]" />
                <span
                    v-for="cm in labels"
                    :key="cm"
                    class="absolute top-1 -translate-x-1/2 font-mono text-[11px]"
                    :style="{ left: `${(cm - min) * 8}px` }"
                >{{ cm }}</span>
            </div>
            <div class="pointer-events-none absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 bg-thread" aria-hidden="true" />
        </div>
        <p class="text-xs text-slate">Lentani suring yoki ← → tugmalaridan foydalaning.</p>
    </div>
</template>

<style scoped>
/* 1 sm = 8 px: mayda chiziq har 8 px, katta (5 sm) har 40 px */
.ruler {
    background-image:
        repeating-linear-gradient(90deg, currentColor 0 1px, transparent 1px 8px),
        repeating-linear-gradient(90deg, currentColor 0 1.5px, transparent 1.5px 40px);
    background-size: auto 10px, auto 22px;
    background-position: 0 bottom, 0 bottom;
    background-repeat: repeat-x;
}
</style>
