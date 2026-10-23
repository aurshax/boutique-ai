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
 * TEST catalogue: styles, fabrics, products, looks and size charts.
 * Prices and measurements are rough, for development and the demo only.
 * Safe to re-run (updateOrCreate by slug).
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
            ['navy-wool', 'Navy wool', '100% wool, Super 110s', FabricPattern::Plain, '#1F2A44'],
            ['charcoal-pinstripe', 'Charcoal pinstripe', '100% wool, Super 120s', FabricPattern::Pinstripe, '#36393F'],
            ['grey-check', 'Grey check (Prince of Wales)', '95% wool, 5% cashmere', FabricPattern::Check, '#7A7D82'],
            ['brown-herringbone', 'Brown herringbone', '100% wool tweed', FabricPattern::Herringbone, '#6B4F3A'],
            ['mid-grey-birdseye', 'Mid-grey birdseye', '100% wool, Super 100s', FabricPattern::Birdseye, '#8A8D91'],
            ['beige-linen', 'Beige linen', '55% linen, 45% cotton', FabricPattern::Plain, '#CDBA96'],
            ['light-grey-flannel', 'Light grey flannel', '100% wool flannel', FabricPattern::Plain, '#A9ACB0'],
            ['olive-tweed', 'Olive tweed', '100% wool, Donegal tweed', FabricPattern::Tweed, '#5E5B3C'],
        ];

        $out = [];
        // Swatch: the AI-generated one first (catalog:generate-images → media/fabrics/swatch-{slug}.webp),
        // then the older Unsplash textures (Home showcase), otherwise null (the UI falls back to the colour).
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
        // description — a VISUAL description: catalog:generate-images uses it for the product photo and
        // PromptBuilder for the try-on prompt. It is not shown in the UI.
        $rows = [
            // Trousers
            ['trousers-navy-classic', ProductType::Trousers, 'Classic navy trousers', 690_000, 'navy-wool',
                'navy blue wool suit trousers, flat front, slim straight leg, pressed centre crease, plain hem'],
            ['trousers-charcoal-pinstripe', ProductType::Trousers, 'Charcoal pinstripe trousers', 790_000, 'charcoal-pinstripe',
                'charcoal grey wool trousers with fine white pinstripes, single forward pleat, straight leg, pressed crease'],
            ['trousers-grey-check', ProductType::Trousers, 'Grey check trousers', 820_000, 'grey-check',
                'grey Prince of Wales check wool trousers, flat front, tapered leg, pressed crease'],
            ['trousers-beige-linen', ProductType::Trousers, 'Beige linen trousers', 590_000, 'beige-linen',
                'beige linen-cotton summer trousers, relaxed straight leg, drawstring-free waistband with belt loops, natural slub texture'],
            ['trousers-light-grey-flannel', ProductType::Trousers, 'Light grey flannel trousers', 760_000, 'light-grey-flannel',
                'light grey wool flannel trousers, double forward pleats, high rise, wide straight leg, turn-up cuffs'],
            // Blazers
            ['blazer-navy', ProductType::Blazer, 'Navy wool blazer', 1_890_000, 'navy-wool',
                'navy blue wool single-breasted two-button blazer, notch lapels, flap pockets, soft natural shoulder'],
            ['blazer-brown-herringbone', ProductType::Blazer, 'Brown herringbone blazer', 2_190_000, 'brown-herringbone',
                'brown herringbone wool sport coat, single-breasted two-button, notch lapels, patch pockets'],
            ['blazer-grey-birdseye', ProductType::Blazer, 'Grey birdseye blazer', 2_050_000, 'mid-grey-birdseye',
                'mid grey birdseye wool suit jacket, single-breasted two-button, notch lapels, jetted pockets'],
            ['blazer-olive-tweed', ProductType::Blazer, 'Olive tweed blazer', 2_290_000, 'olive-tweed',
                'olive green Donegal tweed sport coat with flecks, single-breasted three-roll-two, patch pockets, suede elbow patches'],
            // Shirts
            ['shirt-white-poplin', ProductType::Shirt, 'White poplin shirt', 349_000, null,
                'crisp white cotton poplin dress shirt, semi-spread collar, long sleeves, button cuffs'],
            ['shirt-light-blue-oxford', ProductType::Shirt, 'Light blue oxford shirt', 389_000, null,
                'light blue cotton oxford shirt, button-down collar, long sleeves'],
            ['shirt-cream-knit-polo', ProductType::Shirt, 'Cream knit polo', 429_000, null,
                'cream knitted cotton polo shirt, three-button placket, short sleeves, ribbed collar'],
            ['shirt-pink-oxford', ProductType::Shirt, 'Pink oxford shirt', 389_000, null,
                'pale pink cotton oxford shirt, button-down collar, long sleeves'],
            ['shirt-black-turtleneck', ProductType::Shirt, 'Black merino turtleneck', 459_000, null,
                'black fine merino wool turtleneck sweater, slim fit, long sleeves, ribbed cuffs'],
            // Ties
            ['tie-burgundy-silk', ProductType::Tie, 'Burgundy silk tie', 189_000, null,
                'burgundy silk necktie with a subtle diagonal twill texture, classic 8 cm width'],
            ['tie-navy-grenadine', ProductType::Tie, 'Navy grenadine tie', 219_000, null,
                'navy blue silk grenadine necktie with an open woven texture, 7.5 cm width'],
            ['tie-green-knit', ProductType::Tie, 'Green knit tie', 199_000, null,
                'forest green knitted silk tie with a square bottom end'],
            // Belts
            ['belt-black-leather', ProductType::Belt, 'Black leather belt', 249_000, null,
                'black smooth leather dress belt, 3 cm wide, polished silver rectangular buckle'],
            ['belt-brown-suede', ProductType::Belt, 'Brown suede belt', 269_000, null,
                'dark brown suede casual belt, 3.5 cm wide, brushed brass buckle'],
            // Shoes
            ['shoes-black-oxford', ProductType::Shoes, 'Black oxford shoes', 990_000, null,
                'black polished calf leather cap-toe oxford shoes, closed lacing, leather soles'],
            ['shoes-brown-loafer', ProductType::Shoes, 'Brown suede loafers', 890_000, null,
                'brown suede penny loafers, slip-on, leather soles'],
            ['shoes-dark-brown-derby', ProductType::Shoes, 'Dark brown derby shoes', 940_000, null,
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
     * Which fabrics trousers and blazers can be made in (for fabric swaps).
     *
     * @param  array<string, Product>  $products
     * @param  array<string, Fabric>  $fabrics
     */
    private function seedFabricLinks(array $products, array $fabrics): void
    {
        // fabric slug => surcharge (UZS)
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
     * Size chart (approximate, Uzbekistan/CIS sizes 44–56).
     *
     * @param  array<string, Product>  $products
     */
    private function seedSizeCharts(array $products): void
    {
        // size => [height min, height max, waist min, waist max]
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
                        // Waist is not used for blazers.
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
            ['classic', 'Classic', 'Timeless pieces for formal occasions', 1, 'media/hero/look-black-suit.webp', [
                ['classic-navy-suit', 'Navy Classic', true, ['trousers-navy-classic', 'blazer-navy', 'shirt-white-poplin', 'tie-burgundy-silk', 'belt-black-leather', 'shoes-black-oxford']],
                ['classic-charcoal', 'Charcoal Pinstripe', false, ['trousers-charcoal-pinstripe', 'blazer-grey-birdseye', 'shirt-white-poplin', 'tie-navy-grenadine', 'belt-black-leather', 'shoes-black-oxford']],
            ]],
            ['office', 'Office', 'Everyday office wear, with or without a tie', 2, 'media/hero/look-grey-blazer-stairs.webp', [
                ['office-grey-check', 'Grey Check', false, ['trousers-grey-check', 'blazer-navy', 'shirt-light-blue-oxford', 'belt-black-leather', 'shoes-black-oxford']],
                ['office-navy-oxford', 'Navy & Oxford', false, ['trousers-navy-classic', 'shirt-light-blue-oxford', 'tie-navy-grenadine', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['office-flannel', 'Flannel & Pink', false, ['trousers-light-grey-flannel', 'blazer-navy', 'shirt-pink-oxford', 'tie-green-knit', 'belt-brown-suede', 'shoes-dark-brown-derby']],
            ]],
            ['smart-casual', 'Smart Casual', 'Between formal and relaxed', 3, 'media/hero/look-navy-suit.webp', [
                ['smart-navy-knit', 'Navy Knit', true, ['trousers-navy-classic', 'blazer-navy', 'shirt-cream-knit-polo', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['smart-herringbone', 'Herringbone Evening', false, ['trousers-charcoal-pinstripe', 'blazer-brown-herringbone', 'shirt-cream-knit-polo', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['smart-tweed', 'Tweed & Turtleneck', false, ['trousers-light-grey-flannel', 'blazer-olive-tweed', 'shirt-black-turtleneck', 'belt-brown-suede', 'shoes-dark-brown-derby']],
            ]],
            ['summer', 'Summer', 'Light fabrics for hot days', 4, 'media/hero/look-pinstripe-double-breasted.webp', [
                ['summer-linen', 'Linen Summer', false, ['trousers-beige-linen', 'shirt-white-poplin', 'belt-brown-suede', 'shoes-brown-loafer']],
                ['summer-linen-oxford', 'Linen & Oxford', false, ['trousers-beige-linen', 'shirt-light-blue-oxford', 'belt-brown-suede', 'shoes-brown-loafer']],
            ]],
        ];

        foreach ($styles as [$slug, $name, $description, $sort, $cover, $looks]) {
            $style = Style::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'cover_image' => $cover,
                'sort' => $sort,
            ]);

            // [slug, name, is_suit (jacket and trousers in one fabric), products]
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

    /** The relative path when the file exists under public/, otherwise null (image not generated yet). */
    private function existing(?string $relative): ?string
    {
        return $relative !== null && is_file(public_path($relative)) ? $relative : null;
    }
}
