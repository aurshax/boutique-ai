// Small catalogue for tests (based on CatalogSeeder's "Smart Casual" and "Summer").
// Navy Knit is a suit (is_suit): the blazer's own surcharges differ from the trousers' (for price tests).
import type { Fabric, Style } from '../types';

export const navyWool: Fabric = {
    id: 1,
    name: 'Navy wool',
    composition: '100% wool',
    pattern: 'plain',
    color_hex: '#1F2A44',
    swatch_image: null,
    extra_price_uzs: 0,
};
export const greyCheck: Fabric = {
    id: 3,
    name: 'Grey check',
    composition: '95% wool, 5% cashmere',
    pattern: 'check',
    color_hex: '#7A7D82',
    swatch_image: 'media/fabrics/fabric-navy-check-grey-twill.webp',
    extra_price_uzs: 130_000,
};
export const beigeLinen: Fabric = {
    id: 6,
    name: 'Beige linen',
    composition: '55% linen',
    pattern: 'plain',
    color_hex: '#CDBA96',
    swatch_image: null,
    extra_price_uzs: 0,
};

export const styles: Style[] = [
    {
        id: 3,
        slug: 'smart-casual',
        name: 'Smart Casual',
        description: null,
        looks: [
            {
                id: 5,
                name: 'Navy Knit',
                is_suit: true,
                total_price_uzs: 4_168_000,
                products: [
                    {
                        id: 1,
                        type: 'trousers',
                        type_label: 'Trousers',
                        name: 'Classic navy trousers',
                        price_uzs: 690_000,
                        image: null,
                        fabrics: [greyCheck, navyWool],
                    },
                    {
                        id: 5,
                        type: 'blazer',
                        type_label: 'Blazer',
                        name: 'Navy wool blazer',
                        price_uzs: 1_890_000,
                        image: '/media/catalog/blazer-navy.webp',
                        // check is +200k on the blazer (+130k on the trousers); no linen
                        fabrics: [
                            { ...greyCheck, extra_price_uzs: 200_000 },
                            navyWool,
                        ],
                    },
                    {
                        id: 10,
                        type: 'shirt',
                        type_label: 'Shirt',
                        name: 'Cream knit polo',
                        price_uzs: 429_000,
                        image: null,
                        fabrics: [],
                    },
                    {
                        id: 14,
                        type: 'belt',
                        type_label: 'Belt',
                        name: 'Brown suede belt',
                        price_uzs: 269_000,
                        image: null,
                        fabrics: [],
                    },
                    {
                        id: 16,
                        type: 'shoes',
                        type_label: 'Shoes',
                        name: 'Brown suede loafers',
                        price_uzs: 890_000,
                        image: null,
                        fabrics: [],
                    },
                ],
            },
        ],
    },
    {
        id: 4,
        slug: 'summer',
        name: 'Summer',
        description: null,
        looks: [
            {
                id: 7,
                name: 'Linen Summer',
                is_suit: false,
                total_price_uzs: 2_098_000,
                products: [
                    {
                        id: 4,
                        type: 'trousers',
                        type_label: 'Trousers',
                        name: 'Beige linen trousers',
                        price_uzs: 590_000,
                        image: null,
                        fabrics: [beigeLinen],
                    },
                    {
                        id: 8,
                        type: 'shirt',
                        type_label: 'Shirt',
                        name: 'White poplin shirt',
                        price_uzs: 349_000,
                        image: null,
                        fabrics: [],
                    },
                ],
            },
        ],
    },
    { id: 9, slug: 'empty', name: 'Empty', description: null, looks: [] },
];
