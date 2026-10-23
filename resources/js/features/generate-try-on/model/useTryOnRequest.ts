// Generatsiya jarayoni: so'rov yuborish (202) → holatni polling → natija; o'chirish.
// UI qadamlarini bilmaydi (ularni widgets/try-on-wizard hal qiladi).
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

export function messageForHttpError(error: unknown): string {
    if (!(error instanceof HttpError))
        return "Server bilan aloqa yo'q. Internetni tekshiring.";
    switch (error.status) {
        case 404:
            return "So'rov topilmadi — sessiya tugagan bo'lishi mumkin. Qaytadan boshlang.";
        case 419:
            return 'Sahifa eskirgan. Sahifani yangilang.';
        case 429:
            return "So'rovlar juda ko'p. Bir daqiqadan keyin qayta urinib ko'ring.";
        default:
            return "Xato yuz berdi. Qaytadan urinib ko'ring.";
    }
}

export function useTryOnRequest() {
    const current = shallowRef<TryOnRequest | null>(null);
    const errors = ref<ValidationErrors>({});
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
                generalError.value = messageForHttpError(error);
            },
        },
    );

    function clearErrors(): void {
        errors.value = {};
        generalError.value = null;
    }

    /** true — so'rov qabul qilindi (202). Bir vaqtda ikkinchi submit e'tiborsiz (ikki marta pul yechilmasin). */
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
            else generalError.value = messageForHttpError(error);
            return false;
        } finally {
            submitting.value = false;
        }
    }

    /** Serverda rasmlarni o'chiradi; tugamagan bo'lsa so'rov cancelled bo'ladi. */
    async function remove(): Promise<void> {
        polling.stop();
        const id = current.value?.id;
        current.value = null;
        if (!id) return;
        try {
            await tryOnApi.remove(id);
        } catch {
            // allaqachon o'chgan yoki sessiya tugagan — foydalanuvchi uchun natija bir xil
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
