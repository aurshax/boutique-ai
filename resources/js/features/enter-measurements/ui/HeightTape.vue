<script setup lang="ts">
/**
 * Height picker: a tailor's tape. Drag it (1 cm = 8 px), use the +/− buttons or the keyboard
 * (← → PageUp PageDown Home End). role="slider" for screen readers.
 * On release the tape springs onto the step (CSS transition with overshoot).
 */
import { computed, ref, useId } from 'vue';
import { useI18n } from '@/shared/i18n';

const props = withDefaults(defineProps<{ min?: number; max?: number }>(), {
    min: 120,
    max: 230,
});
const model = defineModel<number>({ required: true });

const { t } = useI18n();
const PX_PER_CM = 8;
const labelId = useId();
const tapeId = useId();
const dragging = ref(false);
let startX = 0;
let startValue = 0;

const clamp = (v: number): number =>
    Math.min(props.max, Math.max(props.min, Math.round(v)));
const offset = computed(() => -(model.value - props.min) * PX_PER_CM);
const labels = computed(() => {
    const out: number[] = [];
    for (let cm = Math.ceil(props.min / 5) * 5; cm <= props.max; cm += 5)
        out.push(cm);
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
    const step: Record<string, number> = {
        ArrowLeft: -1,
        ArrowDown: -1,
        ArrowRight: 1,
        ArrowUp: 1,
        PageDown: -5,
        PageUp: 5,
    };
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
            <span :id="labelId" class="text-base font-semibold">{{
                t('tryOn.height.label')
            }}</span>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    :aria-label="t('tryOn.height.decrease')"
                    class="size-11 rounded-full border border-line bg-white text-xl hover:border-ink"
                    @click="model = clamp(model - 1)"
                >
                    −
                </button>
                <output
                    :for="tapeId"
                    class="min-w-[120px] text-center font-mono text-[28px] font-medium tabular-nums"
                    >{{ t('tryOn.height.value', { cm: model }) }}</output
                >
                <button
                    type="button"
                    :aria-label="t('tryOn.height.increase')"
                    class="size-11 rounded-full border border-line bg-white text-xl hover:border-ink"
                    @click="model = clamp(model + 1)"
                >
                    +
                </button>
            </div>
        </div>

        <div
            :id="tapeId"
            role="slider"
            tabindex="0"
            :aria-labelledby="labelId"
            :aria-valuemin="min"
            :aria-valuemax="max"
            :aria-valuenow="model"
            :aria-valuetext="t('tryOn.height.valueText', { cm: model })"
            class="relative h-12 cursor-grab touch-none overflow-hidden rounded-lg bg-chalk-soft select-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink active:cursor-grabbing"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
            @keydown="onKeydown"
        >
            <!-- the tape: the current value sits under the centre line -->
            <div
                class="absolute inset-y-0 left-1/2 text-ink"
                :class="
                    dragging
                        ? ''
                        : 'transition-transform duration-300 ease-[cubic-bezier(.34,1.56,.64,1)]'
                "
                :style="{
                    transform: `translateX(${offset}px)`,
                    width: `${(max - min) * PX_PER_CM + 1}px`,
                }"
                aria-hidden="true"
            >
                <div class="ruler absolute inset-x-0 bottom-0 h-[22px]" />
                <span
                    v-for="cm in labels"
                    :key="cm"
                    class="absolute top-1 -translate-x-1/2 font-mono text-[11px]"
                    :style="{ left: `${(cm - min) * PX_PER_CM}px` }"
                    >{{ cm }}</span
                >
            </div>
            <div
                class="pointer-events-none absolute inset-y-0 left-1/2 w-0.5 -translate-x-1/2 bg-thread"
                aria-hidden="true"
            />
        </div>
        <p class="text-xs text-slate">
            {{ t('tryOn.height.hint') }}
        </p>
    </div>
</template>

<style scoped>
/* 1 cm = 8 px: a small tick every 8 px, a large one (5 cm) every 40 px */
.ruler {
    background-image:
        repeating-linear-gradient(
            90deg,
            currentColor 0 1px,
            transparent 1px 8px
        ),
        repeating-linear-gradient(
            90deg,
            currentColor 0 1.5px,
            transparent 1.5px 40px
        );
    background-size:
        auto 10px,
        auto 22px;
    background-position:
        0 bottom,
        0 bottom;
    background-repeat: repeat-x;
}
</style>
