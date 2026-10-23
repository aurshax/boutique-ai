import type { App } from 'vue';

/** v-focus — element ko'ringanda fokus (starter-kit formalari ishlatadi). */
export function registerDirectives(app: App): void {
    app.directive('focus', {
        mounted: (el: HTMLElement, binding) => {
            if (binding.value !== false) el.focus();
        },
    });
}
