// Sichqoncha bo'yicha parallax: element ichidagi kursor holati CSS o'zgaruvchilariga (--px, --py, -0.5…0.5)
// yoziladi. Vue qayta render qilmaydi — faqat `transform: translate3d(calc(var(--px) * Npx), ...)` ishlaydi.
// Sensorli ekran va reduced-motion'da o'chiq.
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
