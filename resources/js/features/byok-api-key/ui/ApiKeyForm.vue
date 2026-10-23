<script setup lang="ts">
// BYOK: OpenAI kalitini kiritish. Kalit faqat brauzerda saqlanadi (useApiKey).
import { computed, ref } from 'vue';
import { formatUsdRange } from '@/shared/lib/format';
import { Button } from '@/shared/ui/button';
import { isValidApiKey } from '../model/apiKey';

const props = defineProps<{
    estimate: { medium: number; high: number };
    initialRemember: boolean;
}>();
const emit = defineEmits<{ save: [key: string, remember: boolean] }>();

const draft = ref('');
const remember = ref(props.initialRemember);
const valid = computed(() => isValidApiKey(draft.value));
</script>

<template>
    <section
        aria-labelledby="key-title"
        class="w-full rounded-[20px] bg-white p-6 md:p-8"
    >
        <form
            class="flex flex-col gap-4"
            @submit.prevent="valid && emit('save', draft, remember)"
        >
            <h1 id="key-title" class="font-display text-3xl font-medium">
                OpenAI API kalitingiz
            </h1>
            <p class="max-w-[640px] text-[15px] leading-relaxed text-slate">
                Generatsiya sizning kalitingiz bilan bajariladi (bitta look ≈
                {{ formatUsdRange(estimate.medium, estimate.high) }}). Kalit
                faqat shu brauzerda turadi va serverda saqlanmaydi. Maslahat:
                OpenAI'da alohida project oching va oylik limit qo'ying.
            </p>
            <div class="flex flex-wrap items-end gap-3">
                <label
                    class="flex min-w-[280px] flex-1 flex-col gap-1.5 text-sm text-slate"
                >
                    API kalit
                    <input
                        v-model="draft"
                        type="password"
                        autocomplete="off"
                        spellcheck="false"
                        placeholder="sk-..."
                        :aria-invalid="draft !== '' && !valid"
                        class="h-12 rounded-xl border border-line px-4 font-mono text-base text-ink aria-invalid:border-[#A23C2C]"
                    />
                </label>
                <Button
                    type="submit"
                    variant="brand"
                    size="pill"
                    :disabled="!valid"
                    >Saqlash</Button
                >
            </div>
            <label class="flex items-center gap-2.5 text-sm text-[#3D4553]">
                <input
                    v-model="remember"
                    type="checkbox"
                    class="size-4 accent-ink"
                />
                Shu qurilmada eslab qolish (aks holda tab yopilganda o'chadi)
            </label>
        </form>
    </section>
</template>
