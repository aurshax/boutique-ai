// OpenAI API key format — the single source on the frontend.
// The backend check (CreateTryOnRequest::after()) uses the same pattern.

export const OPENAI_KEY_PATTERN = /^sk-[A-Za-z0-9_-]{20,200}$/;

export function normalizeApiKey(raw: string): string {
    return raw.trim();
}

export function isValidApiKey(raw: string): boolean {
    return OPENAI_KEY_PATTERN.test(normalizeApiKey(raw));
}

/** "sk-proj-abc...a91f" → "sk-…a91f" — never show the full key in the UI. */
export function maskApiKey(raw: string): string {
    const key = normalizeApiKey(raw);
    return key.length > 8 ? `sk-…${key.slice(-4)}` : '';
}
