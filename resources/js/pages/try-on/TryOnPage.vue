<script setup lang="ts">
// Try-on sahifasi. Props: TryOnController::create. Butun logika widgets/try-on-wizard ichida.
import { Head } from '@inertiajs/vue3';
import { MotionConfig } from 'motion-v';
import { TryOnWizard } from '@/widgets/try-on-wizard';
import type { TryOnPageProps } from '@/widgets/try-on-wizard';

const props = defineProps<TryOnPageProps>();
</script>

<template>
    <Head title="Kiyinib ko'ring" />

    <MotionConfig reduced-motion="user">
        <TryOnWizard v-bind="props" />
    </MotionConfig>
</template>
