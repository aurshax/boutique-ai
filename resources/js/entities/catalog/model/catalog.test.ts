import { describe, expect, it } from 'vite-plus/test';
import {
    beigeLinen,
    greyCheck,
    navyWool,
    styles,
} from './__fixtures__/catalog';
import {
    defaultFabricId,
    fabricScope,
    fabricsOf,
    findFabric,
    findLook,
    findStyleOfLook,
    firstLookId,
    followsFabric,
    lookSummary,
    lookTotal,
    productPrice,
    productsWithImages,
    trousersOf,
} from './catalog';
import type { Look } from './types';

const navyKnit = findLook(styles, 5)!; // kostyum
const linenSummer = findLook(styles, 7)!; // oddiy look
const navySeparates: Look = { ...navyKnit, is_suit: false };
const [trousers, blazer, polo] = navyKnit.products;

describe('catalog lookups', () => {
    it('finds look and its style across styles', () => {
        expect(navyKnit.name).toBe('Navy Knit');
        expect(findStyleOfLook(styles, 7)?.slug).toBe('summer');
        expect(findLook(styles, 999)).toBeNull();
    });

    it('returns first look of a style or null for empty style', () => {
        expect(firstLookId(styles, 4)).toBe(7);
        expect(firstLookId(styles, 9)).toBeNull();
    });

    it('finds trousers and products with images', () => {
        expect(trousersOf(navyKnit)?.id).toBe(1);
        expect(productsWithImages(navyKnit).map((p) => p.id)).toEqual([5]);
    });
});

describe('fabric rules', () => {
    it('suit: fabric follows trousers and blazer; separates: trousers only', () => {
        expect(followsFabric(navyKnit, trousers)).toBe(true);
        expect(followsFabric(navyKnit, blazer)).toBe(true);
        expect(followsFabric(navyKnit, polo)).toBe(false);
        expect(followsFabric(navySeparates, blazer)).toBe(false);
    });

    it('suit fabric surcharge is the sum for both pieces', () => {
        expect(fabricsOf(navyKnit)).toEqual([
            { ...greyCheck, extra_price_uzs: 130_000 + 200_000 },
            navyWool,
        ]);
        expect(fabricsOf(navySeparates)).toEqual([greyCheck, navyWool]);
        expect(fabricsOf(null)).toEqual([]);
    });

    it('suit offers only fabrics available for every piece', () => {
        const noLinenBlazer: Look = {
            ...navyKnit,
            products: [
                { ...trousers, fabrics: [navyWool, beigeLinen] },
                blazer,
            ],
        };
        expect(fabricsOf(noLinenBlazer).map((f) => f.id)).toEqual([
            navyWool.id,
        ]);
    });

    it('names the fabric picker by scope', () => {
        expect(fabricScope(navyKnit).label).toBe('Kostyum tkani');
        expect(fabricScope(linenSummer).pieces).toBe('shim');
    });

    it('default fabric prefers no surcharge, falls back to the only one', () => {
        expect(defaultFabricId(navyKnit)).toBe(navyWool.id);
        expect(defaultFabricId(linenSummer)).toBe(beigeLinen.id);
        expect(defaultFabricId(null)).toBeNull();
    });

    it('finds fabric only among the look fabrics', () => {
        expect(findFabric(navyKnit, greyCheck.id)?.extra_price_uzs).toBe(
            330_000,
        );
        expect(findFabric(navyKnit, beigeLinen.id)).toBeNull();
        expect(findFabric(navyKnit, null)).toBeNull();
    });
});

describe('pricing', () => {
    it('each piece gets its own fabric surcharge', () => {
        expect(productPrice(navyKnit, trousers, greyCheck.id)).toBe(820_000);
        expect(productPrice(navyKnit, blazer, greyCheck.id)).toBe(2_090_000);
        expect(productPrice(navySeparates, blazer, greyCheck.id)).toBe(
            1_890_000,
        );
        expect(productPrice(navyKnit, polo, greyCheck.id)).toBe(429_000);
    });

    it('look total equals backend total without fabric and adds surcharges with fabric', () => {
        expect(lookTotal(navyKnit, null)).toBe(navyKnit.total_price_uzs);
        expect(lookTotal(navyKnit, navyWool.id)).toBe(4_168_000);
        expect(lookTotal(navyKnit, greyCheck.id)).toBe(4_168_000 + 330_000);
        expect(lookTotal(navySeparates, greyCheck.id)).toBe(
            4_168_000 + 130_000,
        );
        expect(lookTotal(null, greyCheck.id)).toBe(0);
    });
});

describe('lookSummary', () => {
    it('joins first three product names', () => {
        expect(lookSummary(navyKnit)).toBe(
            "Klassik shim, to'q ko'k · Blazer, to'q ko'k jun · Krem trikotaj polo",
        );
    });
});
