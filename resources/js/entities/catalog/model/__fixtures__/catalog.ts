// Testlar uchun kichik katalog (CatalogSeeder'dagi "Smart Casual" va "Yozgi" asosida).
import type { Fabric, Style } from '../types';

export const navyWool: Fabric = {
    id: 1,
    name: "To'q ko'k jun",
    composition: '100% jun',
    pattern: 'plain',
    color_hex: '#1F2A44',
    swatch_image: null,
    extra_price_uzs: 0,
};
export const greyCheck: Fabric = {
    id: 3,
    name: 'Kulrang katak',
    composition: '95% jun, 5% kashemir',
    pattern: 'check',
    color_hex: '#7A7D82',
    swatch_image: 'media/fabrics/fabric-navy-check-grey-twill.webp',
    extra_price_uzs: 130_000,
};
export const beigeLinen: Fabric = {
    id: 6,
    name: "Bej zig'ir",
    composition: "55% zig'ir",
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
                total_price_uzs: 4_168_000,
                products: [
                    {
                        id: 1,
                        type: 'trousers',
                        type_label: 'Shim',
                        name: "Klassik shim, to'q ko'k",
                        price_uzs: 690_000,
                        image: null,
                        fabrics: [greyCheck, navyWool],
                    },
                    {
                        id: 5,
                        type: 'blazer',
                        type_label: 'Blazer',
                        name: "Blazer, to'q ko'k jun",
                        price_uzs: 1_890_000,
                        image: null,
                        fabrics: [],
                    },
                    {
                        id: 10,
                        type: 'shirt',
                        type_label: "Ko'ylak",
                        name: 'Krem trikotaj polo',
                        price_uzs: 429_000,
                        image: null,
                        fabrics: [],
                    },
                    {
                        id: 14,
                        type: 'belt',
                        type_label: 'Kamar',
                        name: 'Kamar, jigarrang zamsh',
                        price_uzs: 269_000,
                        image: null,
                        fabrics: [],
                    },
                    {
                        id: 16,
                        type: 'shoes',
                        type_label: 'Poyabzal',
                        name: 'Loafer, jigarrang zamsh',
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
        name: 'Yozgi',
        description: null,
        looks: [
            {
                id: 7,
                name: "Zig'ir yoz",
                total_price_uzs: 2_098_000,
                products: [
                    {
                        id: 4,
                        type: 'trousers',
                        type_label: 'Shim',
                        name: "Zig'ir shim, bej",
                        price_uzs: 590_000,
                        image: null,
                        fabrics: [beigeLinen],
                    },
                    {
                        id: 8,
                        type: 'shirt',
                        type_label: "Ko'ylak",
                        name: "Oq ko'ylak, poplin",
                        price_uzs: 349_000,
                        image: null,
                        fabrics: [],
                    },
                ],
            },
        ],
    },
    { id: 9, slug: 'empty', name: "Bo'sh", description: null, looks: [] },
];
