<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { create as tryOn } from '@/routes/try-on';
import { useI18n } from '@/shared/i18n';
import Reveal from '@/shared/ui/Reveal.vue';
import { Button } from '@/shared/ui/button';

const { t } = useI18n();
</script>

<template>
    <section
        class="mx-auto flex max-w-[1360px] flex-col items-start gap-7 px-5 pt-28 pb-24 md:px-10"
    >
        <Reveal
            as="h2"
            class="max-w-[900px] font-display text-5xl leading-[1.02] font-medium md:text-[64px]"
        >
            {{ t('home.cta.title') }}
        </Reveal>
        <Button as-child variant="chalk" size="pill-lg">
            <Link :href="tryOn()">{{ t('nav.tryOn') }}</Link>
        </Button>
    </section>
</template>
