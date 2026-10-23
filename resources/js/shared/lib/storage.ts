// Web Storage'ga xavfsiz kirish: private rejim, bloklangan cookie'lar, SSR yoki test (node) muhitida
// `sessionStorage`/`localStorage` bo'lmasligi yoki xato tashlashi mumkin.

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
            // to'lgan yoki bloklangan — e'tiborsiz
        }
    },
    remove(kind: StorageKind, key: string): void {
        try {
            resolve(kind)?.removeItem(key);
        } catch {
            // e'tiborsiz
        }
    },
};
