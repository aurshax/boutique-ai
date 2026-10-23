// JSON requests to Laravel web routes (fetch). CSRF: read Laravel's `XSRF-TOKEN` cookie and send it back
// as the `X-XSRF-TOKEN` header (axios does this automatically, fetch does not).
import { HttpError } from './api-error';

export type RequestOptions = {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: BodyInit;
    headers?: Record<string, string>;
    signal?: AbortSignal;
};

export function readXsrfToken(
    cookie: string = typeof document === 'undefined' ? '' : document.cookie,
): string | null {
    const match = cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
}

/** 204 → null; 4xx/5xx → HttpError; network failure → TypeError; aborted → AbortError. */
export async function requestJson<T>(
    url: string,
    options: RequestOptions = {},
): Promise<T | null> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };
    const token = readXsrfToken();
    if (token) headers['X-XSRF-TOKEN'] = token;

    const response = await fetch(url, {
        method: options.method ?? 'GET',
        body: options.body,
        headers,
        credentials: 'same-origin',
        signal: options.signal,
    });

    if (response.status === 204) return null;

    const data: unknown = await response.json().catch(() => null);
    if (!response.ok) throw new HttpError(response.status, data);

    return data as T;
}
