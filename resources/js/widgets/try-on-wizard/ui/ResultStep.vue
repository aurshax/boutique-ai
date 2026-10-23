<script setup lang="ts">
// 3-qadam: oldin/keyin, look tarkibi va narxi, taxminiy razmer, tkanni almashtirish, yuklab olish.
import { motion } from 'motion-v';
import { computed } from 'vue';
import { productPrice } from '@/entities/catalog';
import { FabricSwatches } from '@/features/select-fabric';
import { formatUsd, formatUzs } from '@/shared/lib/format';
import { Button } from '@/shared/ui/button';
import { injectTryOnFlow } from '../model/useTryOnFlow';
import BeforeAfter from './BeforeAfter.vue';

const flow = injectTryOnFlow();
const { form, current, swapFabricId, submitting, keyReady, estimate } = flow;
const { photoUrl, look, style, fabric, fabrics, total, heightCm, waistCm } =
    form;

const canSwap = computed(() => swapFabricId.value !== form.fabricId.value);
const measurements = computed(
    () =>
        `bo'y ${heightCm.value} sm${waistCm.value ? ` · bel ${waistCm.value} sm` : ''}`,
);
</script>

<template>
    <div
        class="mx-auto flex max-w-[1360px] flex-wrap items-start gap-8 px-5 py-8 md:px-10"
    >
        <section aria-label="Natija" class="min-w-0 flex-[999_1_560px]">
            <BeforeAfter
                v-if="current?.result_url"
                :before="photoUrl"
                :after="current.result_url"
            />
        </section>

        <aside
            aria-labelledby="look-name"
            class="flex max-w-[460px] flex-[1_1_380px] flex-col gap-4"
        >
            <div class="flex flex-col gap-4 rounded-3xl bg-white p-6">
                <div class="flex flex-col gap-1.5">
                    <span
                        class="font-mono text-xs tracking-[0.12em] text-[#8A6A1E] uppercase"
                        >{{ style?.name }}</span
                    >
                    <h1
                        id="look-name"
                        class="font-display text-[40px] leading-tight font-medium"
                    >
                        {{ look?.name }}
                    </h1>
                </div>
                <ul class="flex flex-col">
                    <motion.li
                        v-for="(p, i) in look?.products ?? []"
                        :key="p.id"
                        class="flex justify-between gap-3 border-b border-paper py-3 text-[15px] last:border-0"
                        :initial="{ opacity: 0, x: 12 }"
                        :animate="{ opacity: 1, x: 0 }"
                        :transition="{ delay: 0.2 + i * 0.07 }"
                    >
                        <span>
                            <span class="text-slate">{{ p.type_label }} · </span
                            >{{ p.name }}
                            <span
                                v-if="p.type === 'trousers' && fabric"
                                class="text-slate"
                            >
                                · {{ fabric.name }}</span
                            >
                        </span>
                        <span class="font-mono text-sm whitespace-nowrap">{{
                            formatUzs(productPrice(p, fabric))
                        }}</span>
                    </motion.li>
                </ul>
                <div class="flex items-baseline justify-between gap-3">
                    <span class="text-[15px] text-slate">Look narxi</span>
                    <span class="font-mono text-2xl font-medium"
                        >{{ formatUzs(total) }} so'm</span
                    >
                </div>
                <div
                    v-if="current?.suggested_size"
                    class="flex items-center justify-between gap-3 rounded-2xl bg-chalk-soft px-4 py-3.5"
                >
                    <span class="text-sm text-[#3D3013]"
                        >Taxminiy razmer<br /><span class="text-xs">{{
                            measurements
                        }}</span></span
                    >
                    <span class="font-mono text-3xl font-medium">{{
                        current.suggested_size
                    }}</span>
                </div>
                <p v-if="current?.cost_usd" class="text-xs text-slate">
                    Sarflandi: {{ formatUsd(Number(current.cost_usd), 4) }}
                </p>
            </div>

            <div
                v-if="fabrics.length > 1"
                class="flex flex-col gap-3.5 rounded-3xl bg-white p-6"
            >
                <FabricSwatches
                    v-model="swapFabricId"
                    :fabrics="fabrics"
                    label="Shimni boshqa tkanda ko'rish"
                />
                <Button
                    v-if="canSwap"
                    variant="brand"
                    size="pill"
                    :disabled="submitting || !keyReady"
                    @click="flow.swapFabric"
                >
                    Shu tkanda ko'rish · ≈ {{ estimate }}
                </Button>
                <p v-else class="text-sm text-slate">
                    Rasm qayta yuklanmaydi — faqat shim yangi tkanda tikiladi.
                </p>
            </div>

            <div class="flex flex-wrap gap-2.5">
                <Button
                    v-if="current?.result_url"
                    as-child
                    variant="brand"
                    size="pill"
                    class="flex-[1_1_140px] text-[#EDEFF3]"
                >
                    <a
                        :href="current.result_url"
                        download="boutique-ai-natija.png"
                        >Yuklab olish</a
                    >
                </Button>
                <Button
                    variant="pill-outline"
                    size="pill"
                    class="flex-[1_1_140px]"
                    @click="flow.editLook"
                    >Boshqa look</Button
                >
            </div>
            <button
                type="button"
                class="self-start py-1.5 text-sm text-[#A23C2C] underline"
                @click="flow.discardPhoto"
            >
                Rasmimni hozir o'chirish
            </button>
        </aside>
    </div>
</template>
