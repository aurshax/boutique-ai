// The customer's own OpenAI key (BYOK). sessionStorage by default — cleared when the tab closes;
// localStorage when "remember" is ticked. The key is never stored on the server.
//
// Module-level state on purpose: one key for the whole app (the header's "remove" and the form
// see the same value). There is no SSR, so nothing can leak between requests.
import { computed, readonly, ref } from 'vue';
import { safeStorage } from '@/shared/lib/storage';
import { isValidApiKey, maskApiKey, normalizeApiKey } from './apiKey';

const STORAGE_KEY = 'boutique.openai_key';

const key = ref(
    safeStorage.get('session', STORAGE_KEY) ??
        safeStorage.get('local', STORAGE_KEY) ??
        '',
);
const remembered = ref(safeStorage.get('local', STORAGE_KEY) !== null);

export function useApiKey() {
    const isValid = computed(() => isValidApiKey(key.value));
    const masked = computed(() => maskApiKey(key.value));

    function save(value: string, remember: boolean): void {
        key.value = normalizeApiKey(value);
        remembered.value = remember;
        safeStorage.set('session', STORAGE_KEY, key.value);
        if (remember) safeStorage.set('local', STORAGE_KEY, key.value);
        else safeStorage.remove('local', STORAGE_KEY);
    }

    function forget(): void {
        key.value = '';
        remembered.value = false;
        safeStorage.remove('session', STORAGE_KEY);
        safeStorage.remove('local', STORAGE_KEY);
    }

    return {
        key: readonly(key),
        remembered: readonly(remembered),
        isValid,
        masked,
        save,
        forget,
    };
}
