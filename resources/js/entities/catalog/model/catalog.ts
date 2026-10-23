// Katalog bo'yicha qidiruv va narx — pure funksiyalar (Vue'siz, test qilinadi).
//
// Tkan qoidasi (backend: Look::followsFabric bilan bir xil):
//   - oddiy look ("separates") — tanlangan tkan faqat SHIMGA qo'llanadi;
//   - kostyum (is_suit) — SHIM va BLAZERGA, ikkalasi bir matodan.
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

/** Shu mahsulot look'da tanlangan tkanda tikiladimi? */
export function followsFabric(look: Look, product: Product): boolean {
    return (
        product.type === 'trousers' ||
        (look.is_suit && product.type === 'blazer')
    );
}

/**
 * Look uchun tanlash mumkin bo'lgan tkanlar. Kostyumda — shim VA blazer uchun mavjud bo'lganlari,
 * `extra_price_uzs` esa ikkalasining ustamasi yig'indisi (UI ko'rsatadigan narx farqi).
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
            if (!own) return []; // kostyumda blazer bu tkanda tikilmaydi — tanlovdan chiqadi
            extra += own.extra_price_uzs;
        }
        return [{ ...fabric, extra_price_uzs: extra }];
    });
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

/** Bitta mahsulot narxi: tkanga bog'liq bo'lsa — shu mahsulotning o'z tkan ustamasi qo'shiladi. */
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

/** Look umumiy narxi (so'm) — backend total_price_uzs (tkansiz) + tanlangan tkan ustamalari. */
export function lookTotal(look: Look | null, fabricId: number | null): number {
    if (!look) return 0;
    return look.products.reduce(
        (sum, p) => sum + productPrice(look, p, fabricId),
        0,
    );
}

/** Look kartochkasidagi qisqa tavsif: birinchi 3 mahsulot nomi. */
export function lookSummary(look: Look, limit = 3): string {
    return look.products
        .slice(0, limit)
        .map((p) => p.name)
        .join(' · ');
}

/** Tkan tanlovi sarlavhasi va izohi: kostyumda — pidjak va shim, aks holda — shim. */
export function fabricScope(look: Look | null): {
    label: string;
    pieces: string;
} {
    return look?.is_suit
        ? { label: 'Kostyum tkani', pieces: 'pidjak va shim' }
        : { label: 'Shim tkani', pieces: 'shim' };
}

/** Rasmi bor mahsulotlar — look kartochkasi va natija ekrani uchun. */
export function productsWithImages(look: Look): Product[] {
    return look.products.filter((p) => p.image !== null);
}
