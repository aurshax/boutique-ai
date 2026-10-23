<script setup lang="ts">
// Vaqtinchalik sahifa — 10-taskda to'liq try-on interfeysi bilan almashtiriladi.
import { Head } from '@inertiajs/vue3';

defineProps<{
    styles: { id: number; name: string; looks: unknown[] }[];
    provider: string;
    requiresApiKey: boolean;
}>();
</script>

<template>
    <Head title="Kiyib ko'ring" />
    <main class="mx-auto max-w-2xl p-8">
        <h1 class="text-2xl font-semibold">Try-on</h1>
        <p class="mt-2 text-sm text-neutral-500">
            Provider: {{ provider }} · API kalit kerak:
            {{ requiresApiKey ? 'ha' : "yo'q" }}
        </p>
        <ul class="mt-6 space-y-1">
            <li v-for="style in styles" :key="style.id">
                {{ style.name }} — {{ style.looks.length }} ta look
            </li>
        </ul>
    </main>
</template>
