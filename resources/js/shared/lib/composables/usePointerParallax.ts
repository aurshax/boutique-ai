// Mouse parallax: the cursor position inside the element is written to CSS variables (--px, --py, -0.5…0.5).
// Vue does not re-render — only `transform: translate3d(calc(var(--px) * Npx), ...)` reacts.
// Disabled on touch screens and with reduced motion.
import { getCurrentScope, onScopeDispose, toValue } from 'vue';
import type { MaybeRefOrGetter, Ref } from 'vue';

export function usePointerParallax(
    target: Ref<HTMLElement | null>,
    disabled: MaybeRefOrGetter<boolean | null | undefined> = false,
) {
    let frame = 0;

    function onPointerMove(event: PointerEvent): void {
        const el = target.value;
        if (toValue(disabled) || !el || event.pointerType !== 'mouse') return;
        const rect = el.getBoundingClientRect();
        const x = (event.clientX - rect.left) / rect.width - 0.5;
        const y = (event.clientY - rect.top) / rect.height - 0.5;
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            el.style.setProperty('--px', x.toFixed(3));
            el.style.setProperty('--py', y.toFixed(3));
        });
    }

    if (getCurrentScope()) onScopeDispose(() => cancelAnimationFrame(frame));

    return { onPointerMove };
}
