// Katalog bo'yicha qidiruv va narx — pure funksiyalar (Vue'siz, test qilinadi).
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

export function fabricsOf(look: Look | null): Fabric[] {
    return trousersOf(look)?.fabrics ?? [];
}

/** Look tanlanganda — narx qo'shmaydigan tkan, bo'lmasa birinchisi, tkan yo'q bo'lsa null. */
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

/** Bitta mahsulot narxi: shim uchun tanlangan tkan ustamasi qo'shiladi. */
export function productPrice(product: Product, fabric: Fabric | null): number {
    return (
        product.price_uzs +
        (product.type === 'trousers' ? (fabric?.extra_price_uzs ?? 0) : 0)
    );
}

/** Look umumiy narxi (so'm) — backend total_price_uzs (tkansiz) + shim tkani ustamasi. */
export function lookTotal(look: Look | null, fabric: Fabric | null): number {
    if (!look) return 0;
    return look.products.reduce((sum, p) => sum + productPrice(p, fabric), 0);
}

/** Look kartochkasidagi qisqa tavsif: birinchi 3 mahsulot nomi. */
export function lookSummary(look: Look, limit = 3): string {
    return look.products
        .slice(0, limit)
        .map((p) => p.name)
        .join(' · ');
}
