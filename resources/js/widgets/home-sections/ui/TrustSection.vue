<script setup lang="ts">
import Reveal from '@/shared/ui/Reveal.vue';
import { TRUST_ITEMS } from '../model/content';
</script>

<template>
    <section
        id="trust"
        class="mx-auto max-w-[1360px] scroll-mt-8 px-5 pt-24 pb-10 md:px-10"
    >
        <Reveal
            class="grid gap-10 rounded-3xl bg-ink-2 p-8 md:grid-cols-3 md:p-14"
        >
            <div
                v-for="item in TRUST_ITEMS"
                :key="item.title"
                class="flex flex-col gap-3"
            >
                <component
                    :is="item.icon"
                    aria-hidden="true"
                    :size="28"
                    :stroke-width="1.6"
                    class="text-chalk"
                />
                <h3 class="text-xl font-semibold">{{ item.title }}</h3>
                <p class="text-[15px] leading-relaxed text-fog">
                    {{ item.text }}
                </p>
            </div>
        </Reveal>
    </section>
</template>
