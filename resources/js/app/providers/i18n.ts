import type { App } from 'vue';
import { i18n } from '@/shared/i18n';

/** Installs vue-i18n and keeps <html lang> in sync with the active locale. */
export function registerI18n(app: App): void {
    app.use(i18n);
    document.documentElement.lang = i18n.global.locale.value;
}
