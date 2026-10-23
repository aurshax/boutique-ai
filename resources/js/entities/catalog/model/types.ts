// Catalogue: Style → Look → Product → Fabric. One aggregate (the backend sends it as a single prop),
// hence one entity slice — separate product/fabric slices would have to import each other.
// Source: TryOnController::create() and HomeController. When the backend shape changes, so does this file.

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
    /** '/media/catalog/...webp' or null (image not generated yet) */
    image: string | null;
    /** Fabrics available for trousers and blazers (surcharge is per product); empty for other types. */
    fabrics: Fabric[];
};

export type Look = {
    id: number;
    name: string;
    /** Jacket and trousers share one fabric (a suit) — the chosen fabric applies to both. */
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

/** Home page showcase (HomeController) — not the full catalogue, a compact card shape. */
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
