<?php

namespace App\Catalog;

use App\Enums\ProductType;
use App\Models\Fabric;
use App\Models\Product;

/**
 * Prompts for catalogue images: every product in one consistent style (white background, studio light,
 * no person), so when passed to a try-on as a garment reference the model sees only the garment.
 */
final class CatalogImagePrompts
{
    private const STYLE = 'Studio e-commerce catalogue photo for a menswear shop. Pure white seamless background, '
        .'soft even light, true-to-life colour and fabric texture, sharp focus, product centred with a small margin. '
        .'No person, no visible mannequin, no hanger, no props, no text, no logo, no watermark.';

    public static function product(Product $product): string
    {
        $what = $product->description ?: $product->name;
        $fabric = $product->defaultFabric;
        if ($fabric !== null && $product->type->supportsFabricSwap()) {
            $what .= ', made of '.self::fabricWords($fabric);
        }

        $presentation = match ($product->type) {
            ProductType::Trousers => 'Laid flat, full length from waistband to hem, front view, legs straight and parallel.',
            ProductType::Blazer => 'Shown on an invisible ghost mannequin, buttoned, front view.',
            ProductType::Shirt => 'Shown on an invisible ghost mannequin, front view, collar neatly shaped.',
            ProductType::Tie => 'Laid flat in a gentle S-curve, full length visible.',
            ProductType::Belt => 'Loosely coiled, buckle facing the camera.',
            ProductType::Shoes => 'A pair, three-quarter side view, one shoe slightly in front of the other.',
        };

        return "Product: {$what}. {$presentation} ".self::STYLE;
    }

    public static function fabric(Fabric $fabric): string
    {
        return 'Close-up flat-lay photo of a suiting fabric swatch filling the whole frame, edge to edge, no background visible. '
            .'Fabric: '.self::fabricWords($fabric).", {$fabric->pattern->value} weave pattern, dominant colour {$fabric->color_hex}. "
            .'Realistic scale of a 10 x 10 cm piece, visible yarn and weave detail, even soft light, no folds, no text, no logo.';
    }

    /** "light-grey-flannel" → "light grey flannel" (slugs are English), plus the composition. */
    private static function fabricWords(Fabric $fabric): string
    {
        return str_replace('-', ' ', $fabric->slug)." fabric ({$fabric->composition})";
    }
}
