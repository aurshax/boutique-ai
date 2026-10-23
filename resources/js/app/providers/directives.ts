import type { App } from 'vue';

/** v-focus — focuses the element when mounted (used by the starter-kit forms). */
export function registerDirectives(app: App): void {
    app.directive('focus', {
        mounted: (el: HTMLElement, binding) => {
            if (binding.value !== false) el.focus();
        },
    });
}
