<script setup lang="ts">
import { useI18n } from '@/shared/i18n';

const { t } = useI18n();
</script>

<template>
    <footer class="border-t border-white/10">
        <div
            class="mx-auto flex max-w-[1360px] flex-wrap justify-between gap-4 px-5 py-7 text-[13px] text-[#7F8899] md:px-10"
        >
            <span>{{ t('home.footer.copyright') }}</span>
            <span>{{ t('home.footer.credits') }}</span>
        </div>
    </footer>
</template>
