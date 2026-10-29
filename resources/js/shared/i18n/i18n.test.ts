import { describe, expect, it } from 'vite-plus/test';
import { i18n } from './index';
import { en } from './locales/en';
import { BRAND_NAME } from '@/shared/config/brand';

/** Every leaf key path in the locale object, e.g. "home.how.steps.0.title". */
function leafKeys(node: unknown, prefix = ''): string[] {
    if (typeof node === 'string') return [prefix];
    return Object.entries(node as Record<string, unknown>).flatMap(([k, v]) =>
        leafKeys(v, prefix ? `${prefix}.${k}` : k),
    );
}

describe('i18n', () => {
    const { t } = i18n.global;
    const keys = leafKeys(en);

    it('compiles every message and never falls back to the raw key', () => {
        for (const key of keys) {
            const out = t(key, { count: 2 }, 2);
            expect(out, key).not.toBe(key);
            expect(out.length, key).toBeGreaterThan(0);
        }
    });

    it('interpolates parameters and pluralises', () => {
        expect(t('common.uzs', { amount: '4,357,000' })).toBe('4,357,000 UZS');
        expect(t('home.styles.looks', 1)).toBe('1 look');
        expect(t('home.styles.looks', 3)).toBe('3 looks');
    });

    it('names the brand from shared/config/brand, never the old working title', () => {
        expect(t('home.footer.copyright')).toContain(BRAND_NAME);
        for (const key of keys)
            expect(t(key, { count: 2 }, 2), key).not.toMatch(/boutique/i);
    });
});
