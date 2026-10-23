<script setup lang="ts">
/**
 * Try-on wizard: setup → waiting → result. Holat — useTryOnFlow (provide), qadamlar uni inject qiladi.
 * Qaysi qadam ko'rinishi faqat model/flow.ts dagi transition() orqali o'zgaradi.
 */
import { AnimatePresence, motion } from 'motion-v';
import { useTryOnFlow, provideTryOnFlow } from '../model/useTryOnFlow';
import type { TryOnPageProps } from '../model/types';
import ResultStep from './ResultStep.vue';
import SetupStep from './SetupStep.vue';
import SubmitBar from './SubmitBar.vue';
import TryOnHeader from './TryOnHeader.vue';
import WaitingStep from './WaitingStep.vue';

const props = defineProps<TryOnPageProps>();

const flow = useTryOnFlow(props);
provideTryOnFlow(flow);
const { step, errorMessage } = flow;

const fade = {
    initial: { opacity: 0 },
    animate: { opacity: 1 },
    exit: { opacity: 0 },
};
const rise = {
    initial: { opacity: 0, y: 16 },
    animate: { opacity: 1, y: 0 },
    exit: { opacity: 0 },
};
</script>

<template>
    <div class="min-h-screen bg-paper pb-28 font-body text-ink antialiased">
        <TryOnHeader :step="step" :requires-api-key="requiresApiKey" />

        <div
            v-if="errorMessage"
            role="alert"
            class="mx-auto mt-6 max-w-[1360px] px-5 md:px-10"
        >
            <p
                class="rounded-2xl border border-[#A23C2C]/30 bg-[#FBEDEA] px-5 py-4 text-[15px] text-[#7A2A1E]"
            >
                {{ errorMessage }}
            </p>
        </div>

        <AnimatePresence mode="wait">
            <motion.main v-if="step === 'setup'" key="setup" v-bind="fade">
                <SetupStep />
            </motion.main>
            <motion.main
                v-else-if="step === 'waiting'"
                key="waiting"
                v-bind="rise"
            >
                <WaitingStep />
            </motion.main>
            <motion.main v-else key="result" v-bind="fade">
                <ResultStep />
            </motion.main>
        </AnimatePresence>

        <SubmitBar v-if="step === 'setup'" />
    </div>
</template>
