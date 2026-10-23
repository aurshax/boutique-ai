<script setup lang="ts">
// Uslub tablari va look kartochkalari. Tanlangan look tikuv chizig'i (dashed) bilan o'raladi.
import { motion } from 'motion-v';
import { computed } from 'vue';
import type { Look, Style } from '@/types/tryon';

const props = defineProps<{ styles: Style[] }>();
const styleId = defineModel<number>('styleId', { required: true });
const lookId = defineModel<number>('lookId', { required: true });

const uzs = (n: number): string => String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
const currentStyle = computed(() => props.styles.find((s) => s.id === styleId.value) ?? props.styles[0]);

function pickStyle(id: number): void {
    styleId.value = id;
    const first = props.styles.find((s) => s.id === id)?.looks[0];
    if (first) lookId.value = first.id;
}

const summary = (look: Look): string => look.products.slice(0, 3).map((p) => p.name).join(' · ');
</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="look-title" class="font-display text-[38px] font-medium">Uslub va look</h2>
            <div role="tablist" aria-label="Uslublar" class="flex flex-wrap gap-1.5 rounded-full bg-white p-1.5">
                <button
                    v-for="style in styles"
                    :key="style.id"
                    type="button"
                    role="tab"
                    :aria-selected="style.id === styleId"
                    class="relative h-10 rounded-full px-[18px] text-[15px]"
                    :class="style.id === styleId ? 'font-semibold text-[#EDEFF3]' : 'text-[#3D4553] hover:text-ink'"
                    @click="pickStyle(style.id)"
                >
                    <motion.span
                        v-if="style.id === styleId"
                        layout-id="style-pill"
                        class="absolute inset-0 rounded-full bg-ink"
                        :transition="{ type: 'spring', stiffness: 400, damping: 32 }"
                    />
                    <span class="relative">{{ style.name }}</span>
                </button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <motion.button
                v-for="(look, i) in currentStyle?.looks ?? []"
                :key="look.id"
                type="button"
                :aria-pressed="look.id === lookId"
                class="flex flex-col gap-3.5 rounded-[20px] border-2 bg-white p-5 text-left transition-colors"
                :class="look.id === lookId ? 'border-dashed border-chalk-deep' : 'border-[#E3E6EB] hover:border-[#8A93A3]'"
                :initial="{ opacity: 0, y: 12 }"
                :animate="{ opacity: 1, y: 0 }"
                :transition="{ delay: i * 0.06 }"
                @click="lookId = look.id"
            >
                <span class="flex items-baseline justify-between gap-2">
                    <span class="text-xl font-semibold">{{ look.name }}</span>
                    <span v-if="look.id === lookId" class="rounded-full bg-chalk-soft px-2.5 py-1 text-xs font-semibold text-[#7A5A12]">Tanlangan</span>
                </span>
                <ul v-if="look.id === lookId" class="flex flex-col gap-2 text-sm">
                    <li v-for="p in look.products" :key="p.id" class="flex justify-between gap-3 border-b border-paper pb-2">
                        <span><span class="text-slate">{{ p.type_label }} · </span>{{ p.name }}</span>
                        <span class="font-mono text-[13px] whitespace-nowrap">{{ uzs(p.price_uzs) }}</span>
                    </li>
                </ul>
                <template v-else>
                    <span class="text-sm leading-relaxed text-slate">{{ summary(look) }}</span>
                    <span class="font-mono text-sm">{{ uzs(look.total_price_uzs) }} so'm</span>
                </template>
            </motion.button>
        </div>
    </div>
</template>
