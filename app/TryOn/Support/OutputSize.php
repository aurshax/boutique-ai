<?php

namespace App\TryOn\Support;

/**
 * Natija o'lchami kirish rasmi nisbatida bo'lsin: nisbat farq qilsa, model sahnani qayta "kompozitsiya" qiladi
 * (poza, qo'llar, fon va yuz o'zgaradi, kadr kesiladi).
 *
 * gpt-image-2 / 2.5 oilasi ixtiyoriy WIDTHxHEIGHT qabul qiladi: ikkala tomon 16 ga karrali, nisbat 1:3…3:1.
 * Eski modellar (gpt-image-1, 1.5) — faqat 1024x1024, 1024x1536, 1536x1024: eng yaqini tanlanadi.
 */
final class OutputSize
{
    public static function for(string $model, int $width, int $height, int $longest = 1536): string
    {
        if ($width < 1 || $height < 1) {
            return '1024x1536';
        }

        if (! str_starts_with($model, 'gpt-image-2')) {
            $ratio = $width / $height;

            return match (true) {
                $ratio < 0.83 => '1024x1536',
                $ratio > 1.2 => '1536x1024',
                default => '1024x1024',
            };
        }

        $ratio = max(1 / 3, min(3, $width / $height));
        [$w, $h] = $ratio <= 1 ? [$longest * $ratio, $longest] : [$longest, $longest / $ratio];

        return self::to16($w).'x'.self::to16($h);
    }

    private static function to16(float $value): int
    {
        return max(16, (int) round($value / 16) * 16);
    }
}
