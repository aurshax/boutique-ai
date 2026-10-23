// Try-on wizard'ining "miyasi": forma + generatsiya (feature) + kalit (feature) + qadamlar mashinasi (flow.ts).
// TryOnWizard.vue uni yaratadi va provide qiladi; qadam komponentlari injectTryOnFlow() bilan oladi.
import { computed, inject, provide, readonly, ref, watch } from 'vue';
import type { InjectionKey } from 'vue';
import { useApiKey } from '@/features/byok-api-key';
import { useTryOnRequest } from '@/features/generate-try-on';
import { formatUsd } from '@/shared/lib/format';
import { transition } from './flow';
import type { Step } from './flow';
import type { TryOnPageProps } from './types';
import { useTryOnForm } from './useTryOnForm';

/** Sahifa ochilganda tanlangan uslub (CatalogSeeder tartibida 3-chisi — Smart Casual) va razmer. */
const DEFAULT_STYLE_INDEX = 2;
const DEFAULT_SIZE = '48';

export function useTryOnFlow(page: TryOnPageProps) {
    const form = useTryOnForm(page.styles, {
        styleIndex: DEFAULT_STYLE_INDEX,
        size: page.sizes.includes(DEFAULT_SIZE) ? DEFAULT_SIZE : null,
    });
    const request = useTryOnRequest();
    const apiKey = useApiKey();

    const step = ref<Step>('setup');
    /** Oxirgi muvaffaqiyatli so'rov — tkan almashtirish va "Boshqa look" rasmni qayta yuklamasdan shundan foydalanadi. */
    const sourceRequestId = ref<string | null>(null);
    /** Natija ekranidagi tkan tanlovi (hali generatsiya qilinmagan). */
    const swapFabricId = ref<number | null>(null);

    const keyReady = computed(
        () => !page.requiresApiKey || apiKey.isValid.value,
    );
    const hasPhoto = computed(
        () => form.photo.value !== null || sourceRequestId.value !== null,
    );
    const canSubmit = computed(
        () =>
            keyReady.value &&
            hasPhoto.value &&
            form.consent.value &&
            form.lookId.value > 0 &&
            !request.submitting.value,
    );
    const estimate = computed(() =>
        formatUsd(page.estimateUsd[form.quality.value]),
    );
    const summary = computed(() =>
        [form.look.value?.name, form.fabric.value?.name]
            .filter(Boolean)
            .join(' · '),
    );
    /** Umumiy xato: tarmoq/HTTP yoki server "failed" holati. */
    const errorMessage = computed(() => {
        if (request.generalError.value) return request.generalError.value;
        const current = request.current.value;
        return current?.status === 'failed'
            ? (current.error ?? 'Xato yuz berdi.')
            : null;
    });

    const fieldError = (field: string): string | null =>
        request.errors.value[field]?.[0] ?? null;

    // Server holati → qadam
    watch(
        () => request.current.value?.status,
        (status) => {
            if (!status) return;
            const next = transition(step.value, { type: 'status', status });
            if (next === 'result' && step.value !== 'result') {
                sourceRequestId.value = request.current.value?.id ?? null;
                swapFabricId.value = form.fabricId.value;
            }
            step.value = next;
        },
    );

    // Yangi rasm tanlansa — eski so'rovga bog'lanish uziladi.
    watch(form.photo, (file) => {
        if (file) sourceRequestId.value = null;
    });

    async function generate(): Promise<void> {
        const accepted = await request.submit(
            {
                photo: form.photo.value,
                sourceRequestId: sourceRequestId.value,
                heightCm: form.heightCm.value,
                waistCm: form.waistCm.value,
                size: form.size.value,
                lookId: form.lookId.value,
                fabricId: form.fabricId.value,
                quality: form.quality.value,
                consent: form.consent.value,
            },
            page.requiresApiKey ? apiKey.key.value : null,
        );
        if (accepted)
            step.value = transition(step.value, { type: 'submitted' });
    }

    async function swapFabric(): Promise<void> {
        form.fabricId.value = swapFabricId.value;
        await generate();
    }

    /** "Kutishni to'xtatish" va "Rasmimni o'chirish": serverda ham, brauzerda ham rasm o'chadi. */
    async function discardPhoto(): Promise<void> {
        await request.remove();
        form.setPhoto(null);
        sourceRequestId.value = null;
        step.value = transition(step.value, { type: 'reset' });
    }

    function editLook(): void {
        request.clearErrors();
        step.value = transition(step.value, { type: 'edit' });
    }

    return {
        page,
        form,
        apiKey,
        step: readonly(step),
        swapFabricId,
        current: request.current,
        submitting: request.submitting,
        keyReady,
        canSubmit,
        estimate,
        summary,
        errorMessage,
        fieldError,
        generate,
        swapFabric,
        discardPhoto,
        editLook,
    };
}

export type TryOnFlow = ReturnType<typeof useTryOnFlow>;

const TryOnFlowKey: InjectionKey<TryOnFlow> = Symbol('TryOnFlow');

export function provideTryOnFlow(flow: TryOnFlow): void {
    provide(TryOnFlowKey, flow);
}

export function injectTryOnFlow(): TryOnFlow {
    const flow = inject(TryOnFlowKey);
    if (!flow)
        throw new Error(
            'injectTryOnFlow(): TryOnWizard ichida ishlating (provideTryOnFlow chaqirilmagan).',
        );
    return flow;
}
