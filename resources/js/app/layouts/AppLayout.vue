<script setup lang="ts">
import AppLayout from '@/app/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItem } from '@/shared/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
