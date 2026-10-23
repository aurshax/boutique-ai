<script setup lang="ts">
import { AppContent } from '@/widgets/app-shell';
import { AppHeader } from '@/widgets/app-shell';
import { AppShell } from '@/widgets/app-shell';
import { Toaster } from '@/shared/ui/sonner';
import type { BreadcrumbItem } from '@/shared/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
