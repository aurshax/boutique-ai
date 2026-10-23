<script setup lang="ts">
// Try-on page. Props: TryOnController::create. All logic lives in widgets/try-on-wizard.
import { Head } from '@inertiajs/vue3';
import { MotionConfig } from 'motion-v';
import { useI18n } from '@/shared/i18n';
import { TryOnWizard } from '@/widgets/try-on-wizard';
import type { TryOnPageProps } from '@/widgets/try-on-wizard';

const props = defineProps<TryOnPageProps>();
const { t } = useI18n();
</script>

<template>
    <Head :title="t('tryOn.metaTitle')" />

    <MotionConfig reduced-motion="user">
        <TryOnWizard v-bind="props" />
    </MotionConfig>
</template>
