<?php

namespace App\TryOn\Support;

/**
 * Keep the output in the input's aspect ratio: when they differ, the model re-composes the scene
 * (pose, hands, background and face change, the frame gets cropped).
 *
 * The gpt-image-2 / 2.5 family accepts any WIDTHxHEIGHT: both sides divisible by 16, ratio 1:3…3:1.
 * Older models (gpt-image-1, 1.5) only take 1024x1024, 1024x1536, 1536x1024: the nearest is used.
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
