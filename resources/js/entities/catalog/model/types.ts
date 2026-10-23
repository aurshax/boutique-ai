// Katalog: Style → Look → Product → Fabric. Bitta agregat (backend uni bitta prop sifatida beradi),
// shuning uchun bitta entity slice — alohida product/fabric slice'lari bir-birini import qilishga majbur bo'lardi.
// Manba: TryOnController::create() va HomeController. Backend shakli o'zgarsa — shu fayl ham.

export type ProductType =
    | 'trousers'
    | 'shirt'
    | 'blazer'
    | 'belt'
    | 'tie'
    | 'shoes';

export type Fabric = {
    id: number;
    name: string;
    composition: string;
    pattern: string;
    color_hex: string;
    swatch_image: string | null;
    extra_price_uzs: number;
};

export type Product = {
    id: number;
    type: ProductType;
    type_label: string;
    name: string;
    price_uzs: number;
    /** '/media/catalog/...webp' yoki null (rasm hali generatsiya qilinmagan) */
    image: string | null;
    /** Shim va blazer uchun mumkin bo'lgan tkanlar (ustama — shu mahsulot uchun); boshqalarda bo'sh. */
    fabrics: Fabric[];
};

export type Look = {
    id: number;
    name: string;
    /** Pidjak va shim bir matodan (kostyum) — tanlangan tkan ikkalasiga qo'llanadi. */
    is_suit: boolean;
    total_price_uzs: number;
    products: Product[];
};

export type Style = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    looks: Look[];
};

/** Home sahifasi vitrinasi (HomeController) — to'liq katalog emas, kartochka uchun qisqa shakl. */
export type StyleCard = {
    slug: string;
    name: string;
    description: string | null;
    cover_image: string | null;
    looks_count: number;
    from_price_uzs: number | null;
};

export type FabricCard = {
    slug: string;
    name: string;
    composition: string;
    swatch_image: string;
    extra_price_uzs: number;
};
