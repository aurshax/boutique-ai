// i18n: vue-i18n (Composition API mode). English is the default; locales live in ./locales.
// Components: `const { t } = useI18n()`. Pure TS code returns message KEYS and lets the UI translate.
import { createI18n } from 'vue-i18n';
import { en } from './locales/en';
import type { MessageSchema } from './locales/en';

export type { MessageSchema };
export type Locale = 'en';

export const i18n = createI18n<[MessageSchema], Locale, false>({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: { en },
    // Keep the number format consistent with the UI copy ("4,357,000 UZS").
    numberFormats: {
        en: { integer: { style: 'decimal', maximumFractionDigits: 0 } },
    },
});

export { useI18n } from 'vue-i18n';
