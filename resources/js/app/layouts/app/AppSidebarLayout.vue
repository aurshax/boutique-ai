<script setup lang="ts">
import { AppContent } from '@/widgets/app-shell';
import { AppShell } from '@/widgets/app-shell';
import { AppSidebar } from '@/widgets/app-shell';
import { AppSidebarHeader } from '@/widgets/app-shell';
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
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="min-w-0 overflow-x-clip">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
