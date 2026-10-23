<?php

namespace App\TryOn;

use App\Enums\ProductType;
use App\Models\Fabric;
use App\Models\Look;
use App\Models\Product;
use App\TryOn\Data\ReferenceImage;

/**
 * Promptni FAQAT server tomonda, katalog ma'lumotlaridan quradi.
 * Foydalanuvchi prompt matnini yozmaydi — prompt injection uchun joy yo'q.
 *
 * Versiya: config('tryon.prompt_version'). Shablon o'zgarsa — versiyani oshiring,
 * shunda eski va yangi natijalarni solishtirish mumkin bo'ladi.
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
        $imageNo = 2;   // 1-rasm — mijozning o'zi

        foreach ($look->products as $product) {
            $line = '- '.$this->describe($product, $fabric);

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
            $fabricLine = "The trousers must be made of this fabric: {$fabric->name} — {$fabric->composition}, "
                ."pattern: {$fabric->pattern->value}, main colour {$fabric->color_hex}.";
            if ($swatch !== null) {
                $references[] = new ReferenceImage($swatch, 'fabric swatch', "fabric-{$fabric->id}.jpg");
                $fabricLine .= " Match the weave, pattern scale and colour of the fabric swatch in image {$imageNo} exactly.";
            }
        }

        $prompt = implode("\n", array_filter([
            'Virtual try-on for a menswear shop.',
            'Image 1 is the customer. Keep his face, identity, hair, skin tone, body shape, pose and the background exactly as they are.',
            "The customer is {$heightCm} cm tall; keep realistic proportions and trouser length for that height.",
            'Dress him in this outfit:',
            ...$garmentLines,
            $fabricLine,
            'Tailoring must look real: natural drape and creases, correct fit at the waist and hem, no extra garments.',
            'Photorealistic, same lighting as the original photo. Do not add text, logos or watermarks.',
        ]));

        return ['prompt' => $prompt, 'references' => $references];
    }

    private function describe(Product $product, ?Fabric $fabric): string
    {
        $text = "{$product->type->value}: {$product->name}";

        if ($product->type === ProductType::Trousers && $fabric !== null) {
            $text .= " (in {$fabric->name})";
        }

        return $text;
    }

    /** public/media/... ichidagi rasm. Katalog rasmlari hali yo'q bo'lsa — null, matnli tavsif ishlatiladi. */
    private function publicImage(?string $relative): ?string
    {
        if ($relative === null || str_contains($relative, '..')) {
            return null;
        }

        $path = public_path(ltrim($relative, '/'));

        return is_file($path) ? (string) file_get_contents($path) : null;
    }
}
