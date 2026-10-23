<script setup lang="ts">
// Error under a field (422 validation or a browser-side check). Renders nothing when empty.
defineProps<{
    message?: string | null;
}>();
</script>

<template>
    <p
        v-if="message"
        role="alert"
        class="text-sm text-red-600 dark:text-red-500"
    >
        {{ message }}
    </p>
</template>
