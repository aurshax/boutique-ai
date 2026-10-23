// Mijozning OpenAI kaliti (BYOK). Odatda sessionStorage — tab yopilsa o'chadi.
// "Eslab qolish" belgilansa — localStorage. Kalit hech qachon serverda saqlanmaydi.
import { computed, ref } from 'vue';

const STORAGE_KEY = 'boutique.openai_key';

function read(storage: Storage): string {
    try {
        return storage.getItem(STORAGE_KEY) ?? '';
    } catch {
        return '';   // private rejim yoki bloklangan storage
    }
}

const key = ref<string>(typeof window === 'undefined' ? '' : read(sessionStorage) || read(localStorage));
const remember = ref<boolean>(typeof window === 'undefined' ? false : read(localStorage) !== '');

export function useApiKey() {
    const isValidFormat = computed(() => /^sk-[A-Za-z0-9_-]{20,200}$/.test(key.value.trim()));
    const masked = computed(() => (key.value.length > 8 ? `sk-…${key.value.slice(-4)}` : ''));

    function save(value: string, rememberIt: boolean): void {
        key.value = value.trim();
        remember.value = rememberIt;
        try {
            sessionStorage.setItem(STORAGE_KEY, key.value);
            if (rememberIt) {
                localStorage.setItem(STORAGE_KEY, key.value);
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        } catch {
            // storage yo'q — kalit faqat shu sahifa xotirasida qoladi
        }
    }

    function forget(): void {
        key.value = '';
        remember.value = false;
        try {
            sessionStorage.removeItem(STORAGE_KEY);
            localStorage.removeItem(STORAGE_KEY);
        } catch {
            // e'tiborsiz
        }
    }

    return { key, remember, isValidFormat, masked, save, forget };
}
