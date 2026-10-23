// Safe Web Storage access: in private mode, with blocked cookies, during SSR or in tests (node)
// `sessionStorage`/`localStorage` may be missing or throw.

export type StorageKind = 'session' | 'local';

function resolve(kind: StorageKind): Storage | null {
    try {
        if (typeof window === 'undefined') return null;
        return kind === 'session' ? window.sessionStorage : window.localStorage;
    } catch {
        return null;
    }
}

export const safeStorage = {
    get(kind: StorageKind, key: string): string | null {
        try {
            return resolve(kind)?.getItem(key) ?? null;
        } catch {
            return null;
        }
    },
    set(kind: StorageKind, key: string, value: string): void {
        try {
            resolve(kind)?.setItem(key, value);
        } catch {
            // full or blocked — ignore
        }
    },
    remove(kind: StorageKind, key: string): void {
        try {
            resolve(kind)?.removeItem(key);
        } catch {
            // ignore
        }
    },
};
