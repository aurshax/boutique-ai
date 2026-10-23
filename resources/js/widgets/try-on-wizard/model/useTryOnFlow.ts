// The try-on wizard's "brain": form + generation (feature) + API key (feature) + step machine (flow.ts).
// TryOnWizard.vue creates and provides it; the step components get it via injectTryOnFlow().
import { computed, inject, provide, readonly, ref, watch } from 'vue';
import type { InjectionKey } from 'vue';
import { useApiKey } from '@/features/byok-api-key';
import { useTryOnRequest } from '@/features/generate-try-on';
import { formatUsd } from '@/shared/lib/format';
import { transition } from './flow';
import type { Step } from './flow';
import type { TryOnPageProps } from './types';
import { useTryOnForm } from './useTryOnForm';

/** Style selected on load (the 3rd in CatalogSeeder order — Smart Casual) and the default size. */
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
    /** Last successful request — fabric swaps and "Another look" reuse its photo instead of uploading again. */
    const sourceRequestId = ref<string | null>(null);
    /** Fabric picked on the result screen (not generated yet). */
    const swapFabricId = ref<number | null>(null);

    /** Real AI runs with the visitor's key; in demo mode ('optional') a missing key means the free mock. */
    const usesRealAi = computed(
        () =>
            page.keyMode === 'required' ||
            (page.keyMode === 'optional' && apiKey.isValid.value),
    );
    const keyReady = computed(
        () => page.keyMode !== 'required' || apiKey.isValid.value,
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
    /**
     * Page-level error: an i18n key (network/HTTP) or a server message that is already translated
     * (API-key validation, a failed generation).
     */
    const errorMessage = computed<{ key: string } | { text: string } | null>(
        () => {
            if (request.generalError.value)
                return { key: request.generalError.value };
            // The key error goes to the banner, not under a field (the key form may be collapsed).
            const keyError = request.errors.value.api_key?.[0];
            if (keyError) return { text: keyError };
            const current = request.current.value;
            if (current?.status !== 'failed') return null;
            return current.error
                ? { text: current.error }
                : { key: 'tryOn.errors.generic' };
        },
    );

    const fieldError = (field: string): string | null =>
        request.errors.value[field]?.[0] ?? null;

    // Server status → step
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

    // A new photo detaches the wizard from the previous request.
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
            usesRealAi.value ? apiKey.key.value : null,
        );
        if (accepted)
            step.value = transition(step.value, { type: 'submitted' });
    }

    async function swapFabric(): Promise<void> {
        form.fabricId.value = swapFabricId.value;
        await generate();
    }

    /** "Stop waiting" and "Delete my photo": the photo is removed on the server and in the browser. */
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
        usesRealAi,
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
            'injectTryOnFlow(): use it inside TryOnWizard (provideTryOnFlow() was not called).',
        );
    return flow;
}
