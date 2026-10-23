// Try-on so'rovi hayot sikli: yuborish → har 2 s status so'rash → done/failed.
import { onBeforeUnmount, ref } from 'vue';
import { destroy, show, store } from '@/actions/App/Http/Controllers/TryOnController';
import { HttpError, requestJson } from '@/lib/http';
import type { TryOnState, ValidationErrors } from '@/types/tryon';

const POLL_MS = 2000;

export type SubmitPayload = {
    photo: File | null;
    sourceRequestId: string | null;
    heightCm: number;
    waistCm: number | null;
    size: string | null;
    lookId: number;
    fabricId: number | null;
    quality: string;
    consent: boolean;
    apiKey: string | null;
};

export function useTryOnRequest() {
    const current = ref<TryOnState | null>(null);
    const errors = ref<ValidationErrors>({});
    const generalError = ref<string | null>(null);
    const submitting = ref(false);
    let timer: ReturnType<typeof setTimeout> | null = null;

    function stopPolling(): void {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
    }

    async function poll(id: string): Promise<void> {
        try {
            const state = await requestJson<TryOnState>(show.url(id));
            if (state) {
                current.value = state;
            }
            if (state && ['queued', 'running'].includes(state.status)) {
                timer = setTimeout(() => void poll(id), POLL_MS);
            }
        } catch (error) {
            generalError.value = error instanceof HttpError && error.status === 404
                ? "So'rov topilmadi — sessiya tugagan bo'lishi mumkin. Qaytadan boshlang."
                : "Holatni olib bo'lmadi. Internetni tekshiring.";
        }
    }

    async function submit(payload: SubmitPayload): Promise<boolean> {
        stopPolling();
        errors.value = {};
        generalError.value = null;
        submitting.value = true;

        const form = new FormData();
        if (payload.photo) form.append('photo', payload.photo);
        if (payload.sourceRequestId) form.append('source_request_id', payload.sourceRequestId);
        form.append('height_cm', String(payload.heightCm));
        if (payload.waistCm) form.append('waist_cm', String(payload.waistCm));
        if (payload.size) form.append('size', payload.size);
        form.append('look_id', String(payload.lookId));
        if (payload.fabricId) form.append('fabric_id', String(payload.fabricId));
        form.append('quality', payload.quality);
        form.append('consent', payload.consent ? '1' : '0');

        try {
            const state = await requestJson<TryOnState>(store.url(), {
                method: 'POST',
                body: form,
                // Kalit faqat sarlavhada — server uni darhol so'rovdan olib tashlaydi (ExtractOpenAiKey).
                headers: payload.apiKey ? { 'X-OpenAI-Key': payload.apiKey } : {},
            });
            if (!state) return false;
            current.value = state;
            timer = setTimeout(() => void poll(state.id), POLL_MS);
            return true;
        } catch (error) {
            if (error instanceof HttpError && error.status === 422) {
                errors.value = (error.body as { errors?: ValidationErrors })?.errors ?? {};
            } else if (error instanceof HttpError && error.status === 429) {
                generalError.value = "So'rovlar juda ko'p. Bir daqiqadan keyin qayta urinib ko'ring.";
            } else {
                generalError.value = "So'rovni yuborib bo'lmadi. Qaytadan urinib ko'ring.";
            }
            return false;
        } finally {
            submitting.value = false;
        }
    }

    /** Kutishni to'xtatish yoki "Rasmimni o'chir": serverda rasmlar o'chadi, so'rov cancelled bo'ladi. */
    async function remove(id: string): Promise<void> {
        stopPolling();
        try {
            await requestJson(destroy.url(id), { method: 'DELETE' });
        } catch {
            // allaqachon o'chgan bo'lishi mumkin
        }
    }

    onBeforeUnmount(stopPolling);

    return { current, errors, generalError, submitting, submit, remove, stopPolling };
}
