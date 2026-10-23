<?php

namespace App\TryOn;

use App\Models\Fabric;
use App\Models\Look;
use App\Models\Product;
use App\TryOn\Data\ReferenceImage;

/**
 * Builds the prompt on the SERVER only, from catalogue data.
 * The user never writes prompt text — there is no room for prompt injection.
 *
 * Version: config('tryon.prompt_version'). Bump it whenever the template changes,
 * so old and new results can be compared.
 */
class PromptBuilder
{
    /**
     * @return array{prompt: string, references: list<ReferenceImage>}
     */
    public function build(Look $look, ?Fabric $fabric, int $heightCm): array
    {
        $look->loadMissing('products');

        $references = [];
        $garmentLines = [];
        $imageNo = 2;   // image 1 is the customer

        foreach ($look->products as $product) {
            $line = '- '.$this->describe($product, $look->followsFabric($product) ? $fabric : null);

            $bytes = $this->publicImage($product->image);
            if ($bytes !== null) {
                $references[] = new ReferenceImage($bytes, $product->type->value, "product-{$product->id}.jpg");
                $line .= " (see image {$imageNo})";
                $imageNo++;
            }

            $garmentLines[] = $line;
        }

        $fabricLine = '';
        if ($fabric !== null) {
            $swatch = $this->publicImage($fabric->swatch_image);
            $target = $look->is_suit
                ? 'The jacket and trousers are a matching suit: both must be made of this same fabric'
                : 'The trousers must be made of this fabric';
            $fabricLine = "{$target}: {$fabric->name} — {$fabric->composition}, "
                ."pattern: {$fabric->pattern->value}, main colour {$fabric->color_hex}."
                // Catalogue photos show the default fabric — with another fabric they only define the cut.
                .' Garment images show the cut and style only; take colour and pattern for these pieces from the fabric description.';
            if ($swatch !== null) {
                $references[] = new ReferenceImage($swatch, 'fabric swatch', "fabric-{$fabric->id}.jpg");
                $fabricLine .= " Match the weave, pattern scale and colour of the fabric swatch in image {$imageNo} exactly.";
            }
        }

        // v2: lock everything except the clothes — face, pose, framing, background (v1 redrew the face).
        // v3: the model used to slim people down (cheeks, belly) when dressing them — lock body and face fullness.
        $prompt = implode("\n", array_filter([
            'Virtual try-on for a menswear shop. This is a clothing-only edit of image 1, not a new photo.',
            'Image 1 is the customer. Keep his face, identity, hair, skin tone, body shape, pose and the background exactly as they are.',
            'Do not redraw, beautify, slim, age or retouch the face or head: same facial features, expression, glasses or beard, same head size and position.',
            'Keep his real body weight and figure exactly: same belly, chest, shoulder width, waist and cheek fullness. Do not slim, flatter or idealise him.',
            'The new garments must be tailored to his actual figure, the way they would really fit him, including around the stomach.',
            'Keep the exact framing, camera angle, crop and scale of image 1. Keep arms, hands and legs in the same positions. The whole body stays visible from head to feet.',
            "The customer is {$heightCm} cm tall; keep realistic proportions and trouser length for that height.",
            'Replace only his clothes with this outfit:',
            ...$garmentLines,
            $fabricLine,
            'Tailoring must look real: natural drape and creases, correct fit at the waist and hem, no extra garments.',
            'Photorealistic, same lighting, colours and image quality as the original photo. Do not add text, logos or watermarks.',
        ]));

        return ['prompt' => $prompt, 'references' => $references];
    }

    private function describe(Product $product, ?Fabric $fabric): string
    {
        // The English visual description (seeder) when present, otherwise the product name.
        $text = "{$product->type->value}: ".($product->description ?: $product->name);

        if ($fabric !== null) {   // passed only for products the fabric applies to (Look::followsFabric)
            $text .= " (in {$fabric->name})";
        }

        return $text;
    }

    /** An image under public/media/... (catalog_media_path). Null when it does not exist yet — the text description is used instead. */
    private function publicImage(?string $relative): ?string
    {
        if ($relative === null || str_contains($relative, '..')) {
            return null;
        }

        $path = rtrim((string) config('tryon.catalog_media_path', public_path()), '/').'/'.ltrim($relative, '/');

        return is_file($path) ? (string) file_get_contents($path) : null;
    }
}
