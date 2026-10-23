// Try-on sahifasi uchun turlar. Backend: TryOnController::create() va ::present().

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
    type: 'trousers' | 'shirt' | 'blazer' | 'belt' | 'tie' | 'shoes';
    type_label: string;
    name: string;
    price_uzs: number;
    image: string | null;
    fabrics: Fabric[];
};

export type Look = {
    id: number;
    name: string;
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

export type Quality = 'medium' | 'high';

export type TryOnStatus = 'queued' | 'running' | 'done' | 'failed' | 'cancelled';

export type TryOnState = {
    id: string;
    status: TryOnStatus;
    error: string | null;
    result_url: string | null;
    has_photo: boolean;
    suggested_size: string | null;
    cost_usd: string | null;
    look_id: number | null;
    fabric_id: number | null;
    created_at: string | null;
};

export type ValidationErrors = Record<string, string[]>;
