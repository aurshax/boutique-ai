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
        ];

        $out = [];
        // Swatch rasmlari (public/media/fabrics, Unsplash). Rasm yo'q tkanlar UI'da rang bilan ko'rsatiladi.
        // Eslatma: bu namunaviy teksturalar — real do'konda o'z matosining rasmi bo'ladi.
        $swatches = [
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
                'swatch_image' => $swatches[$slug] ?? null,
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
        // [slug, type, name, price_uzs, default_fabric]
        $rows = [
            // Shimlar
            ['trousers-navy-classic', ProductType::Trousers, "Klassik shim, to'q ko'k", 690_000, 'navy-wool'],
            ['trousers-charcoal-pinstripe', ProductType::Trousers, 'Pinstripe shim, antrasit', 790_000, 'charcoal-pinstripe'],
            ['trousers-grey-check', ProductType::Trousers, 'Katak shim, kulrang', 820_000, 'grey-check'],
            ['trousers-beige-linen', ProductType::Trousers, "Zig'ir shim, bej", 590_000, 'beige-linen'],
            // Blazerlar
            ['blazer-navy', ProductType::Blazer, "Blazer, to'q ko'k jun", 1_890_000, 'navy-wool'],
            ['blazer-brown-herringbone', ProductType::Blazer, 'Blazer, jigarrang herringbone', 2_190_000, 'brown-herringbone'],
            ['blazer-grey-birdseye', ProductType::Blazer, 'Blazer, kulrang birdseye', 2_050_000, 'mid-grey-birdseye'],
            // Ko'ylaklar
            ['shirt-white-poplin', ProductType::Shirt, "Oq ko'ylak, poplin", 349_000, null],
            ['shirt-light-blue-oxford', ProductType::Shirt, "Och havorang ko'ylak, oxford", 389_000, null],
            ['shirt-cream-knit-polo', ProductType::Shirt, 'Krem trikotaj polo', 429_000, null],
            // Galstuklar
            ['tie-burgundy-silk', ProductType::Tie, "Galstuk, to'q qizil ipak", 189_000, null],
            ['tie-navy-grenadine', ProductType::Tie, "Galstuk, to'q ko'k grenadin", 219_000, null],
            // Kamarlar
            ['belt-black-leather', ProductType::Belt, 'Kamar, qora teri', 249_000, null],
            ['belt-brown-suede', ProductType::Belt, 'Kamar, jigarrang zamsh', 269_000, null],
            // Poyabzal
            ['shoes-black-oxford', ProductType::Shoes, 'Oxford tufli, qora', 990_000, null],
            ['shoes-brown-loafer', ProductType::Shoes, 'Loafer, jigarrang zamsh', 890_000, null],
        ];

        $out = [];
        foreach ($rows as [$slug, $type, $name, $price, $fabricSlug]) {
            $out[$slug] = Product::updateOrCreate(['slug' => $slug], [
                'type' => $type,
                'name' => $name,
                'price_uzs' => $price,
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
                ['classic-navy-suit', "To'q ko'k klassika", ['trousers-navy-classic', 'blazer-navy', 'shirt-white-poplin', 'tie-burgundy-silk', 'belt-black-leather', 'shoes-black-oxford']],
                ['classic-charcoal', 'Antrasit pinstripe', ['trousers-charcoal-pinstripe', 'blazer-grey-birdseye', 'shirt-white-poplin', 'tie-navy-grenadine', 'belt-black-leather', 'shoes-black-oxford']],
            ]],
            ['office', 'Office', "Har kunlik ofis uchun, galstuksiz ham bo'ladi", 2, 'media/hero/look-grey-blazer-stairs.webp', [
                ['office-grey-check', 'Kulrang katak', ['trousers-grey-check', 'blazer-navy', 'shirt-light-blue-oxford', 'belt-black-leather', 'shoes-black-oxford']],
                ['office-navy-oxford', "To'q ko'k va oxford", ['trousers-navy-classic', 'shirt-light-blue-oxford', 'tie-navy-grenadine', 'belt-brown-suede', 'shoes-brown-loafer']],
            ]],
            ['smart-casual', 'Smart Casual', 'Rasmiy va erkin uslub orasida', 3, 'media/hero/look-navy-suit.webp', [
                ['smart-navy-knit', 'Navy Knit', ['trousers-navy-classic', 'blazer-navy', 'shirt-cream-knit-polo', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['smart-herringbone', 'Herringbone kech', ['trousers-charcoal-pinstripe', 'blazer-brown-herringbone', 'shirt-cream-knit-polo', 'belt-brown-suede', 'shoes-brown-loafer']],
            ]],
            ['summer', 'Yozgi', "Issiq kunlar uchun yengil matolar", 4, 'media/hero/look-pinstripe-double-breasted.webp', [
                ['summer-linen', "Zig'ir yoz", ['trousers-beige-linen', 'shirt-white-poplin', 'belt-brown-suede', 'shoes-brown-loafer']],
            ]],
        ];

        foreach ($styles as [$slug, $name, $description, $sort, $cover, $looks]) {
            $style = Style::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'cover_image' => $cover,
                'sort' => $sort,
            ]);

            foreach ($looks as $i => [$lookSlug, $lookName, $productSlugs]) {
                $look = Look::updateOrCreate(['slug' => $lookSlug], [
                    'style_id' => $style->id,
                    'name' => $lookName,
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
}
