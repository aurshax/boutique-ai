<script setup lang="ts">
// Maydon ostidagi xato (422 validatsiya yoki brauzer tekshiruvi). Bo'sh bo'lsa hech narsa chizilmaydi.
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
