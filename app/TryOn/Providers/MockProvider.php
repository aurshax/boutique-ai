<?php

namespace App\TryOn\Providers;

use App\TryOn\Contracts\TryOnProvider;
use App\TryOn\Data\TryOnInput;
use App\TryOn\Data\TryOnResult;
use App\TryOn\Exceptions\TryOnProviderException;
use GdImage;

/**
 * Development uchun soxta provider — API'ga pul ketmaydi.
 * Mijoz rasmini oladi, pastki qismiga to'q ko'k "shim" tasmasini va "MOCK" yozuvini chizadi.
 * Kechikish (config tryon.mock.delay_seconds) kutish animatsiyasini sinash uchun.
 */
class MockProvider implements TryOnProvider
{
    public function name(): string
    {
        return 'mock';
    }

    public function generate(TryOnInput $input): TryOnResult
    {
        $delay = (int) config('tryon.mock.delay_seconds', 0);
        if ($delay > 0) {
            sleep($delay);
        }

        $image = @imagecreatefromstring($input->personImage);
        if (! $image instanceof GdImage) {
            throw new TryOnProviderException('Mock: mijoz rasmini o\'qib bo\'lmadi.');
        }

        $w = imagesx($image);
        $h = imagesy($image);

        // Pastki 45% ni yarim shaffof to'q ko'k bilan bo'yaymiz — "shim" o'rnida.
        $navy = imagecolorallocatealpha($image, 31, 42, 68, 50);
        if ($navy !== false) {
            imagefilledrectangle($image, 0, (int) ($h * 0.55), $w, $h, $navy);
        }

        $chalk = imagecolorallocate($image, 224, 180, 90);
        if ($chalk !== false) {
            imagestring($image, 5, 12, 12, 'MOCK TRY-ON', $chalk);
        }

        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        return new TryOnResult(
            imageBytes: $png,
            model: 'mock-v1',
            costUsd: '0.0000',
            usage: ['references' => count($input->references), 'prompt_chars' => strlen($input->prompt)],
        );
    }
}
