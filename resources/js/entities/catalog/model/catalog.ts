// Catalogue lookups and pricing — pure functions (no Vue, unit tested).
//
// Fabric rule (same as the backend's Look::followsFabric):
//   - separates — the chosen fabric applies to the TROUSERS only;
//   - suit (is_suit) — to the TROUSERS and the BLAZER, both in one fabric.
import type { Fabric, Look, Product, Style } from './types';

export function findLook(styles: Style[], lookId: number): Look | null {
    for (const style of styles) {
        const look = style.looks.find((l) => l.id === lookId);
        if (look) return look;
    }
    return null;
}

export function findStyleOfLook(styles: Style[], lookId: number): Style | null {
    return styles.find((s) => s.looks.some((l) => l.id === lookId)) ?? null;
}

export function firstLookId(styles: Style[], styleId: number): number | null {
    return styles.find((s) => s.id === styleId)?.looks[0]?.id ?? null;
}

export function trousersOf(look: Look | null): Product | null {
    return look?.products.find((p) => p.type === 'trousers') ?? null;
}

/** Is this product made in the look's chosen fabric? */
export function followsFabric(look: Look, product: Product): boolean {
    return (
        product.type === 'trousers' ||
        (look.is_suit && product.type === 'blazer')
    );
}

/**
 * Fabrics the look can be made in. For a suit — those available for both trousers AND blazer,
 * with `extra_price_uzs` being the sum of both surcharges (the price difference the UI shows).
 */
export function fabricsOf(look: Look | null): Fabric[] {
    if (!look) return [];
    const targets = look.products.filter((p) => followsFabric(look, p));
    const trousers = targets.find((p) => p.type === 'trousers');
    if (!trousers) return [];

    return trousers.fabrics.flatMap((fabric) => {
        let extra = 0;
        for (const product of targets) {
            const own = product.fabrics.find((f) => f.id === fabric.id);
            if (!own) return []; // the suit's blazer is not made in this fabric — drop it from the choice
            extra += own.extra_price_uzs;
        }
        return [{ ...fabric, extra_price_uzs: extra }];
    });
}

/** On look change: the fabric with no surcharge, else the first one, null when there are none. */
export function defaultFabricId(look: Look | null): number | null {
    const fabrics = fabricsOf(look);
    return (
        (fabrics.find((f) => f.extra_price_uzs === 0) ?? fabrics[0])?.id ?? null
    );
}

export function findFabric(
    look: Look | null,
    fabricId: number | null,
): Fabric | null {
    if (fabricId === null) return null;
    return fabricsOf(look).find((f) => f.id === fabricId) ?? null;
}

/** One product's price: adds this product's own surcharge when it follows the fabric. */
export function productPrice(
    look: Look,
    product: Product,
    fabricId: number | null,
): number {
    if (fabricId === null || !followsFabric(look, product))
        return product.price_uzs;
    const own = product.fabrics.find((f) => f.id === fabricId);
    return product.price_uzs + (own?.extra_price_uzs ?? 0);
}

/** Look total (UZS) — the backend total_price_uzs (no fabric) plus the chosen fabric's surcharges. */
export function lookTotal(look: Look | null, fabricId: number | null): number {
    if (!look) return 0;
    return look.products.reduce(
        (sum, p) => sum + productPrice(look, p, fabricId),
        0,
    );
}

/** Short summary for the look card: the first 3 product names. */
export function lookSummary(look: Look, limit = 3): string {
    return look.products
        .slice(0, limit)
        .map((p) => p.name)
        .join(' · ');
}

/** i18n keys for the fabric picker: for a suit it covers jacket and trousers, otherwise the trousers. */
export function fabricScope(look: Look | null): {
    labelKey: string;
    piecesKey: string;
} {
    return look?.is_suit
        ? {
              labelKey: 'tryOn.fabric.suit',
              piecesKey: 'tryOn.fabric.piecesSuit',
          }
        : {
              labelKey: 'tryOn.fabric.trousers',
              piecesKey: 'tryOn.fabric.piecesTrousers',
          };
}

/** Products that have an image — for the look card and the result screen. */
export function productsWithImages(look: Look): Product[] {
    return look.products.filter((p) => p.image !== null);
}
