// Ilova kirish nuqtasi (vite.config.ts → laravel.input). Faqat ulash: sahifalar, layout'lar, provider'lar.
// Qatlamlar (FSD): app → pages → widgets → features → entities → shared (faqat pastga import).
import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/shared/lib/appearance/useAppearance';
import { resolveLayout } from './layouts/resolveLayout';
import { registerDirectives } from './providers/directives';
import { initializeFlashToast } from './providers/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    // @inertiajs/vite shu yo'ldagi .vue fayllarni glob qiladi: "try-on/TryOnPage" → ../pages/try-on/TryOnPage.vue
    pages: '../pages',
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: resolveLayout,
    withApp: registerDirectives,
    progress: {
        color: '#4B5563',
    },
});

// Yorug'/qorong'i rejim (starter-kit sozlamalari sahifasi uchun)
initializeTheme();

// Server flash xabarlari → toast
initializeFlashToast();
