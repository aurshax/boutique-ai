// Laravel web route'lariga JSON so'rovlar (fetch). CSRF: Laravel `XSRF-TOKEN` cookie'sini o'qib,
// `X-XSRF-TOKEN` sarlavhasida qaytaramiz (axios buni avtomatik qiladi, fetch — yo'q).

export class HttpError extends Error {
    constructor(
        public status: number,
        public body: unknown,
    ) {
        super(`HTTP ${status}`);
    }
}

function xsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : null;
}

export async function requestJson<T>(
    url: string,
    options: { method?: string; body?: BodyInit; headers?: Record<string, string> } = {},
): Promise<T | null> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...options.headers,
    };
    const token = xsrfToken();
    if (token) {
        headers['X-XSRF-TOKEN'] = token;
    }

    const response = await fetch(url, {
        method: options.method ?? 'GET',
        body: options.body,
        headers,
        credentials: 'same-origin',
    });

    if (response.status === 204) {
        return null;
    }

    const data: unknown = await response.json().catch(() => null);
    if (!response.ok) {
        throw new HttpError(response.status, data);
    }

    return data as T;
}
