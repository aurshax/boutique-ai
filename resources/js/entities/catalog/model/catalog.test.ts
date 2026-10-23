import { describe, expect, it } from 'vite-plus/test';
import {
    beigeLinen,
    greyCheck,
    navyWool,
    styles,
} from './__fixtures__/catalog';
import {
    defaultFabricId,
    fabricsOf,
    findFabric,
    findLook,
    findStyleOfLook,
    firstLookId,
    lookSummary,
    lookTotal,
    productPrice,
    trousersOf,
} from './catalog';

const navyKnit = findLook(styles, 5);

describe('catalog lookups', () => {
    it('finds look and its style across styles', () => {
        expect(navyKnit?.name).toBe('Navy Knit');
        expect(findStyleOfLook(styles, 7)?.slug).toBe('summer');
        expect(findLook(styles, 999)).toBeNull();
    });

    it('returns first look of a style or null for empty style', () => {
        expect(firstLookId(styles, 4)).toBe(7);
        expect(firstLookId(styles, 9)).toBeNull();
    });

    it('finds trousers and their fabrics', () => {
        expect(trousersOf(navyKnit)?.id).toBe(1);
        expect(fabricsOf(navyKnit)).toEqual([greyCheck, navyWool]);
        expect(fabricsOf(null)).toEqual([]);
    });
});

describe('defaultFabricId', () => {
    it('prefers the fabric without surcharge', () => {
        expect(defaultFabricId(navyKnit)).toBe(navyWool.id);
    });

    it('falls back to the only fabric and to null', () => {
        expect(defaultFabricId(findLook(styles, 7))).toBe(beigeLinen.id);
        expect(defaultFabricId(null)).toBeNull();
    });
});

describe('pricing', () => {
    it('adds fabric surcharge only to trousers', () => {
        const trousers = trousersOf(navyKnit)!;
        const blazer = navyKnit!.products[1];
        expect(productPrice(trousers, greyCheck)).toBe(690_000 + 130_000);
        expect(productPrice(blazer, greyCheck)).toBe(1_890_000);
    });

    it('look total equals backend total without fabric and adds surcharge with fabric', () => {
        expect(lookTotal(navyKnit, null)).toBe(navyKnit!.total_price_uzs);
        expect(lookTotal(navyKnit, navyWool)).toBe(4_168_000);
        expect(lookTotal(navyKnit, greyCheck)).toBe(4_168_000 + 130_000);
        expect(lookTotal(null, greyCheck)).toBe(0);
    });

    it('finds fabric only among the look trousers fabrics', () => {
        expect(findFabric(navyKnit, greyCheck.id)).toEqual(greyCheck);
        expect(findFabric(navyKnit, beigeLinen.id)).toBeNull();
        expect(findFabric(navyKnit, null)).toBeNull();
    });
});

describe('lookSummary', () => {
    it('joins first three product names', () => {
        expect(lookSummary(navyKnit!)).toBe(
            "Klassik shim, to'q ko'k · Blazer, to'q ko'k jun · Krem trikotaj polo",
        );
    });
});
