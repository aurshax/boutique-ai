// The generation lifecycle: submit (202) → poll the status → result; delete.
// Knows nothing about UI steps (widgets/try-on-wizard owns those).
import { readonly, ref, shallowRef } from 'vue';
import { isPending, tryOnApi } from '@/entities/try-on-request';
import type {
    CreateTryOnPayload,
    TryOnRequest,
} from '@/entities/try-on-request';
import { HttpError } from '@/shared/api';
import type { ValidationErrors } from '@/shared/api';
import { usePolling } from '@/shared/lib/composables/usePolling';

export const POLL_INTERVAL_MS = 2000;

/** HTTP / network error → i18n key of a user-facing message (translated by the UI). */
export function messageKeyForHttpError(error: unknown): string {
    if (!(error instanceof HttpError)) return 'tryOn.errors.network';
    switch (error.status) {
        case 404:
            return 'tryOn.errors.notFound';
        case 419:
            return 'tryOn.errors.expired';
        case 429:
            return 'tryOn.errors.tooMany';
        default:
            return 'tryOn.errors.generic';
    }
}

export function useTryOnRequest() {
    const current = shallowRef<TryOnRequest | null>(null);
    const errors = ref<ValidationErrors>({});
    /** i18n key of a network/HTTP error (validation errors go to `errors`). */
    const generalError = ref<string | null>(null);
    const submitting = ref(false);

    const polling = usePolling(
        (signal) => tryOnApi.get(current.value!.id, signal),
        {
            interval: POLL_INTERVAL_MS,
            shouldContinue: (state) => isPending(state?.status),
            onResult: (state) => {
                if (state) current.value = state;
            },
            onError: (error) => {
                generalError.value = messageKeyForHttpError(error);
            },
        },
    );

    function clearErrors(): void {
        errors.value = {};
        generalError.value = null;
    }

    /** true = accepted (202). A second submit while one is in flight is ignored (never charge twice). */
    async function submit(
        payload: CreateTryOnPayload,
        apiKey: string | null = null,
    ): Promise<boolean> {
        if (submitting.value) return false;
        polling.stop();
        clearErrors();
        submitting.value = true;

        try {
            const state = await tryOnApi.create(payload, apiKey);
            if (!state) return false;
            current.value = state;
            polling.start();
            return true;
        } catch (error) {
            if (error instanceof HttpError && error.isValidation)
                errors.value = error.validationErrors;
            else generalError.value = messageKeyForHttpError(error);
            return false;
        } finally {
            submitting.value = false;
        }
    }

    /** Deletes the images on the server; an unfinished request becomes cancelled. */
    async function remove(): Promise<void> {
        polling.stop();
        const id = current.value?.id;
        current.value = null;
        if (!id) return;
        try {
            await tryOnApi.remove(id);
        } catch {
            // already deleted or the session expired — same outcome for the user
        }
    }

    return {
        current: readonly(current),
        errors: readonly(errors),
        generalError: readonly(generalError),
        submitting: readonly(submitting),
        submit,
        remove,
        clearErrors,
    };
}

export type TryOnRequestState = ReturnType<typeof useTryOnRequest>;
