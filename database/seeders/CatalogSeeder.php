<?php

namespace Database\Seeders;

use App\Enums\FabricPattern;
use App\Enums\ProductType;
use App\Models\Fabric;
use App\Models\Look;
use App\Models\Product;
use App\Models\SizeChart;
use App\Models\Style;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TEST katalogi: uslublar, tkanlar, mahsulotlar, look'lar va razmer jadvallari.
 * Narxlar va o'lchamlar taxminiy, faqat development uchun.
 * Qayta ishga tushirsa bo'ladi (updateOrCreate — slug bo'yicha).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $fabrics = $this->seedFabrics();
            $products = $this->seedProducts($fabrics);
            $this->seedFabricLinks($products, $fabrics);
            $this->seedSizeCharts($products);
            $this->seedStylesAndLooks($products);
        });
    }

    /** @return array<string, Fabric> */
    private function seedFabrics(): array
    {
        $rows = [
            ['navy-wool', "To'q ko'k jun", '100% jun, Super 110s', FabricPattern::Plain, '#1F2A44'],
            ['charcoal-pinstripe', 'Antrasit pinstripe', '100% jun, Super 120s', FabricPattern::Pinstripe, '#36393F'],
            ['grey-check', 'Kulrang katak (Prince of Wales)', "95% jun, 5% kashemir", FabricPattern::Check, '#7A7D82'],
            ['brown-herringbone', 'Jigarrang herringbone', '100% jun, tvid', FabricPattern::Herringbone, '#6B4F3A'],
            ['mid-grey-birdseye', "O'rta kulrang birdseye", '100% jun, Super 100s', FabricPattern::Birdseye, '#8A8D91'],
            ['beige-linen', "Bej zig'ir", "55% zig'ir, 45% paxta", FabricPattern::Plain, '#CDBA96'],
            ['light-grey-flannel', 'Och kulrang flanel', '100% jun, flanel', FabricPattern::Plain, '#A9ACB0'],
            ['olive-tweed', 'Zaytun rang tvid', '100% jun, Donegal tvid', FabricPattern::Tweed, '#5E5B3C'],
        ];

        $out = [];
        // Swatch: avval AI bilan yaratilgan namuna (catalog:generate-images → media/fabrics/swatch-{slug}.webp),
        // bo'lmasa — Unsplash'dagi eski teksturalar (Home vitrinasi uchun), bo'lmasa — null (UI rang bilan ko'rsatadi).
        $legacy = [
            'grey-check' => 'media/fabrics/fabric-navy-check-grey-twill.webp',
            'mid-grey-birdseye' => 'media/fabrics/fabric-olive-houndstooth.webp',
            'brown-herringbone' => 'media/fabrics/fabric-camel-houndstooth-brown.webp',
        ];

        foreach ($rows as [$slug, $name, $composition, $pattern, $hex]) {
            $out[$slug] = Fabric::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'composition' => $composition,
                'pattern' => $pattern,
                'color_hex' => $hex,
                'swatch_image' => $this->existing("media/fabrics/swatch-{$slug}.webp") ?? $this->existing($legacy[$slug] ?? null),
            ]);
        }

        return $out;
    }

    /**
     * @param  array<string, Fabric>  $fabrics
     * @return array<string, Product>
     */
    private function seedProducts(array $fabrics): array
    {
        // [slug, type, name, price_uzs, default_fabric, description (EN)]
        // description — inglizcha VIZUAL tavsif: catalog:generate-images uni rasm uchun, PromptBuilder esa
        // try-on promptida ishlatadi (model inglizcha tavsifni aniqroq tushunadi). UI'da ko'rsatilmaydi.
        $rows = [
            // Shimlar
            ['trousers-navy-classic', ProductType::Trousers, "Klassik shim, to'q ko'k", 690_000, 'navy-wool',
                'navy blue wool suit trousers, flat front, slim straight leg, pressed centre crease, plain hem'],
            ['trousers-charcoal-pinstripe', ProductType::Trousers, 'Pinstripe shim, antrasit', 790_000, 'charcoal-pinstripe',
                'charcoal grey wool trousers with fine white pinstripes, single forward pleat, straight leg, pressed crease'],
            ['trousers-grey-check', ProductType::Trousers, 'Katak shim, kulrang', 820_000, 'grey-check',
                'grey Prince of Wales check wool trousers, flat front, tapered leg, pressed crease'],
            ['trousers-beige-linen', ProductType::Trousers, "Zig'ir shim, bej", 590_000, 'beige-linen',
                'beige linen-cotton summer trousers, relaxed straight leg, drawstring-free waistband with belt loops, natural slub texture'],
            ['trousers-light-grey-flannel', ProductType::Trousers, 'Flanel shim, och kulrang', 760_000, 'light-grey-flannel',
                'light grey wool flannel trousers, double forward pleats, high rise, wide straight leg, turn-up cuffs'],
            // Blazerlar
            ['blazer-navy', ProductType::Blazer, "Blazer, to'q ko'k jun", 1_890_000, 'navy-wool',
                'navy blue wool single-breasted two-button blazer, notch lapels, flap pockets, soft natural shoulder'],
            ['blazer-brown-herringbone', ProductType::Blazer, 'Blazer, jigarrang herringbone', 2_190_000, 'brown-herringbone',
                'brown herringbone wool sport coat, single-breasted two-button, notch lapels, patch pockets'],
            ['blazer-grey-birdseye', ProductType::Blazer, 'Blazer, kulrang birdseye', 2_050_000, 'mid-grey-birdseye',
                'mid grey birdseye wool suit jacket, single-breasted two-button, notch lapels, jetted pockets'],
            ['blazer-olive-tweed', ProductType::Blazer, 'Blazer, zaytun tvid', 2_290_000, 'olive-tweed',
                'olive green Donegal tweed sport coat with flecks, single-breasted three-roll-two, patch pockets, suede elbow patches'],
            // Ko'ylaklar
            ['shirt-white-poplin', ProductType::Shirt, "Oq ko'ylak, poplin", 349_000, null,
                'crisp white cotton poplin dress shirt, semi-spread collar, long sleeves, button cuffs'],
            ['shirt-light-blue-oxford', ProductType::Shirt, "Och havorang ko'ylak, oxford", 389_000, null,
                'light blue cotton oxford shirt, button-down collar, long sleeves'],
            ['shirt-cream-knit-polo', ProductType::Shirt, 'Krem trikotaj polo', 429_000, null,
                'cream knitted cotton polo shirt, three-button placket, short sleeves, ribbed collar'],
            ['shirt-pink-oxford', ProductType::Shirt, "Pushti ko'ylak, oxford", 389_000, null,
                'pale pink cotton oxford shirt, button-down collar, long sleeves'],
            ['shirt-black-turtleneck', ProductType::Shirt, 'Qora merino turtleneck', 459_000, null,
                'black fine merino wool turtleneck sweater, slim fit, long sleeves, ribbed cuffs'],
            // Galstuklar
            ['tie-burgundy-silk', ProductType::Tie, "Galstuk, to'q qizil ipak", 189_000, null,
                'burgundy silk necktie with a subtle diagonal twill texture, classic 8 cm width'],
            ['tie-navy-grenadine', ProductType::Tie, "Galstuk, to'q ko'k grenadin", 219_000, null,
                'navy blue silk grenadine necktie with an open woven texture, 7.5 cm width'],
            ['tie-green-knit', ProductType::Tie, 'Galstuk, yashil trikotaj', 199_000, null,
                'forest green knitted silk tie with a square bottom end'],
            // Kamarlar
            ['belt-black-leather', ProductType::Belt, 'Kamar, qora teri', 249_000, null,
                'black smooth leather dress belt, 3 cm wide, polished silver rectangular buckle'],
            ['belt-brown-suede', ProductType::Belt, 'Kamar, jigarrang zamsh', 269_000, null,
                'dark brown suede casual belt, 3.5 cm wide, brushed brass buckle'],
            // Poyabzal
            ['shoes-black-oxford', ProductType::Shoes, 'Oxford tufli, qora', 990_000, null,
                'black polished calf leather cap-toe oxford shoes, closed lacing, leather soles'],
            ['shoes-brown-loafer', ProductType::Shoes, 'Loafer, jigarrang zamsh', 890_000, null,
                'brown suede penny loafers, slip-on, leather soles'],
            ['shoes-dark-brown-derby', ProductType::Shoes, 'Derby tufli, to\'q jigarrang', 940_000, null,
                'dark brown burnished leather plain-toe derby shoes, open lacing, rubber commando soles'],
        ];

        $out = [];
        foreach ($rows as [$slug, $type, $name, $price, $fabricSlug, $description]) {
            $out[$slug] = Product::updateOrCreate(['slug' => $slug], [
                'type' => $type,
                'name' => $name,
                'description' => $description,
                'price_uzs' => $price,
                'image' => $this->existing("media/catalog/{$slug}.webp"),
                'default_fabric_id' => $fabricSlug ? $fabrics[$fabricSlug]->id : null,
                'is_active' => true,
            ]);
        }

        return $out;
    }

    /**
     * Shim va blazerlarni qaysi tkanlarda tikish mumkin (tkan almashtirish uchun).
     *
     * @param  array<string, Product>  $products
     * @param  array<string, Fabric>  $fabrics
     */
    private function seedFabricLinks(array $products, array $fabrics): void
    {
        // tkan slug => ustama narx (so'm)
        $woolOptions = [
            'navy-wool' => 0,
            'charcoal-pinstripe' => 100_000,
            'grey-check' => 130_000,
            'mid-grey-birdseye' => 80_000,
            'brown-herringbone' => 150_000,
            'light-grey-flannel' => 60_000,
            'olive-tweed' => 170_000,
        ];

        foreach ($products as $product) {
            if (! $product->type->supportsFabricSwap()) {
                continue;
            }

            $options = $product->slug === 'trousers-beige-linen'
                ? ['beige-linen' => 0]
                : $woolOptions;

            $sync = [];
            foreach ($options as $fabricSlug => $extra) {
                $sync[$fabrics[$fabricSlug]->id] = ['extra_price_uzs' => $extra];
            }
            $product->fabrics()->sync($sync);
        }
    }

    /**
     * Razmer jadvali (taxminiy, O'zbekiston/MDH razmerlari 44–56).
     *
     * @param  array<string, Product>  $products
     */
    private function seedSizeCharts(array $products): void
    {
        // razmer => [bo'y min, bo'y max, bel min, bel max]
        $chart = [
            '44' => [158, 172, 70, 75],
            '46' => [164, 178, 76, 80],
            '48' => [168, 182, 81, 86],
            '50' => [170, 186, 87, 92],
            '52' => [172, 188, 93, 98],
            '54' => [174, 190, 99, 104],
            '56' => [176, 194, 105, 110],
        ];

        foreach ($products as $product) {
            if (! in_array($product->type, [ProductType::Trousers, ProductType::Blazer], true)) {
                continue;
            }

            foreach ($chart as $size => [$hMin, $hMax, $wMin, $wMax]) {
                SizeChart::updateOrCreate(
                    ['product_id' => $product->id, 'size' => (string) $size],
                    [
                        'height_cm' => [$hMin, $hMax],
                        // Blazer uchun bel o'lchami ishlatilmaydi.
                        'waist_cm' => $product->type === ProductType::Trousers ? [$wMin, $wMax] : null,
                    ],
                );
            }
        }
    }

    /** @param  array<string, Product>  $products */
    private function seedStylesAndLooks(array $products): void
    {
        $styles = [
            ['classic', 'Classic', "Vaqt sinovidan o'tgan, rasmiy uchrashuvlar uchun", 1, 'media/hero/look-black-suit.webp', [
                ['classic-navy-suit', "To'q ko'k klassika", true, ['trousers-navy-classic', 'blazer-navy', 'shirt-white-poplin', 'tie-burgundy-silk', 'belt-black-leather', 'shoes-black-oxford']],
                ['classic-charcoal', 'Antrasit pinstripe', false, ['trousers-charcoal-pinstripe', 'blazer-grey-birdseye', 'shirt-white-poplin', 'tie-navy-grenadine', 'belt-black-leather', 'shoes-black-oxford']],
            ]],
            ['office', 'Office', "Har kunlik ofis uchun, galstuksiz ham bo'ladi", 2, 'media/hero/look-grey-blazer-stairs.webp', [
                ['office-grey-check', 'Kulrang katak', false, ['trousers-grey-check', 'blazer-navy', 'shirt-light-blue-oxford', 'belt-black-leather', 'shoes-black-oxford']],
                ['office-navy-oxford', "To'q ko'k va oxford", false, ['trousers-navy-classic', 'shirt-light-blue-oxford', 'tie-navy-grenadine', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['office-flannel', 'Flanel va pushti', false, ['trousers-light-grey-flannel', 'blazer-navy', 'shirt-pink-oxford', 'tie-green-knit', 'belt-brown-suede', 'shoes-dark-brown-derby']],
            ]],
            ['smart-casual', 'Smart Casual', 'Rasmiy va erkin uslub orasida', 3, 'media/hero/look-navy-suit.webp', [
                ['smart-navy-knit', 'Navy Knit', true, ['trousers-navy-classic', 'blazer-navy', 'shirt-cream-knit-polo', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['smart-herringbone', 'Herringbone kech', false, ['trousers-charcoal-pinstripe', 'blazer-brown-herringbone', 'shirt-cream-knit-polo', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['smart-tweed', 'Tvid va turtleneck', false, ['trousers-light-grey-flannel', 'blazer-olive-tweed', 'shirt-black-turtleneck', 'belt-brown-suede', 'shoes-dark-brown-derby']],
            ]],
            ['summer', 'Yozgi', "Issiq kunlar uchun yengil matolar", 4, 'media/hero/look-pinstripe-double-breasted.webp', [
                ['summer-linen', "Zig'ir yoz", false, ['trousers-beige-linen', 'shirt-white-poplin', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['summer-linen-oxford', "Zig'ir va oxford", false, ['trousers-beige-linen', 'shirt-light-blue-oxford', 'belt-brown-suede', 'shoes-brown-loafer']],
            ]],
        ];

        foreach ($styles as [$slug, $name, $description, $sort, $cover, $looks]) {
            $style = Style::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'cover_image' => $cover,
                'sort' => $sort,
            ]);

            // [slug, nom, is_suit (pidjak va shim bir matodan), mahsulotlar]
            foreach ($looks as $i => [$lookSlug, $lookName, $isSuit, $productSlugs]) {
                $look = Look::updateOrCreate(['slug' => $lookSlug], [
                    'style_id' => $style->id,
                    'name' => $lookName,
                    'is_suit' => $isSuit,
                    'sort' => $i + 1,
                ]);

                $sync = [];
                foreach ($productSlugs as $position => $productSlug) {
                    $sync[$products[$productSlug]->id] = ['position' => $position + 1];
                }
                $look->products()->sync($sync);
            }
        }
    }

    /** public/ ichida fayl bo'lsa — nisbiy yo'l, aks holda null (rasm hali generatsiya qilinmagan). */
    private function existing(?string $relative): ?string
    {
        return $relative !== null && is_file(public_path($relative)) ? $relative : null;
    }
}
