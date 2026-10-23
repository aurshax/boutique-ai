// Application entry (vite.config.ts → laravel.input). Wiring only: pages, layouts, providers.
// Layers (Feature-Sliced Design): app → pages → widgets → features → entities → shared (imports go down only).
import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/shared/lib/appearance/useAppearance';
import { resolveLayout } from './layouts/resolveLayout';
import { registerDirectives } from './providers/directives';
import { initializeFlashToast } from './providers/flashToast';
import { registerI18n } from './providers/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    // @inertiajs/vite globs .vue files under this path: "try-on/TryOnPage" → ../pages/try-on/TryOnPage.vue
    pages: '../pages',
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: resolveLayout,
    withApp: (app) => {
        registerI18n(app);
        registerDirectives(app);
    },
    progress: {
        color: '#4B5563',
    },
});

// Light/dark mode (used by the starter-kit settings pages)
initializeTheme();

// Server flash messages → toasts
initializeFlashToast();
