// Try-on endpoints (Wayfinder URLs + shared/api). Knows nothing about UI or state — requests only.
import {
    destroy,
    show,
    store,
} from '@/actions/App/Http/Controllers/TryOnController';
import { requestJson } from '@/shared/api';
import type { CreateTryOnPayload, TryOnRequest } from '../model/types';

/** The BYOK key is sent only in this header; the server strips it right away (ExtractOpenAiKey). */
export const API_KEY_HEADER = 'X-OpenAI-Key';

export function toFormData(payload: CreateTryOnPayload): FormData {
    const form = new FormData();
    if (payload.sourceRequestId)
        form.append('source_request_id', payload.sourceRequestId);
    else if (payload.photo) form.append('photo', payload.photo);
    form.append('height_cm', String(payload.heightCm));
    if (payload.waistCm) form.append('waist_cm', String(payload.waistCm));
    if (payload.size) form.append('size', payload.size);
    form.append('look_id', String(payload.lookId));
    if (payload.fabricId) form.append('fabric_id', String(payload.fabricId));
    form.append('quality', payload.quality);
    form.append('consent', payload.consent ? '1' : '0');
    return form;
}

export const tryOnApi = {
    create(
        payload: CreateTryOnPayload,
        apiKey: string | null,
    ): Promise<TryOnRequest | null> {
        return requestJson<TryOnRequest>(store.url(), {
            method: 'POST',
            body: toFormData(payload),
            headers: apiKey ? { [API_KEY_HEADER]: apiKey } : {},
        });
    },

    get(id: string, signal?: AbortSignal): Promise<TryOnRequest | null> {
        return requestJson<TryOnRequest>(show.url(id), { signal });
    },

    /** Deletes the images; an unfinished request becomes `cancelled`. */
    async remove(id: string): Promise<void> {
        await requestJson(destroy.url(id), { method: 'DELETE' });
    },
};
