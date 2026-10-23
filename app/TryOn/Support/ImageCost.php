<?php

namespace App\TryOn\Support;

/**
 * OpenAI javobidagi `usage` dan haqiqiy narxni hisoblaydi (USD).
 * Narxlar — config('tryon.openai.pricing'), 1M token uchun. Natija decimal satr ("0.0512"):
 * float yig'indisi DB'ga yozilganda yaxlitlash xatosi to'planmasin.
 */
final class ImageCost
{
    /**
     * @param  array<string, mixed>  $usage  {input_tokens, input_tokens_details: {text_tokens, image_tokens}, output_tokens}
     * @param  array{text_input: float|int, image_input: float|int, image_output: float|int}  $pricePerMillion
     */
    public static function fromUsage(array $usage, array $pricePerMillion): ?string
    {
        if (! isset($usage['output_tokens'])) {
            return null;
        }

        $details = is_array($usage['input_tokens_details'] ?? null) ? $usage['input_tokens_details'] : [];
        $textIn = (int) ($details['text_tokens'] ?? 0);
        $imageIn = (int) ($details['image_tokens'] ?? 0);

        // Batafsil bo'linma bo'lmasa — butun kirishni rasm narxida hisoblaymiz (yuqori baho, kam emas).
        if ($textIn === 0 && $imageIn === 0) {
            $imageIn = (int) ($usage['input_tokens'] ?? 0);
        }

        $micro = $textIn * $pricePerMillion['text_input']
            + $imageIn * $pricePerMillion['image_input']
            + (int) $usage['output_tokens'] * $pricePerMillion['image_output'];

        return number_format($micro / 1_000_000, 4, '.', '');
    }
}
