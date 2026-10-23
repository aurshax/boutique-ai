<?php

namespace App\TryOn;

use App\TryOn\Exceptions\InvalidImageException;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Rasmlar bilan ishlash (faqat PHP GD — qo'shimcha paket yo'q).
 *
 * Mijoz rasmi har doim QAYTA KODLANADI. Bu bir vaqtda:
 *  - EXIF/GPS va boshqa metadata'ni o'chiradi (shaxsiy ma'lumot),
 *  - fayl ichiga yashirilgan payload'ni yo'q qiladi,
 *  - o'lchamni kichraytiradi (OpenAI kirish tokenlari = narx).
 */
class ImageService
{
    /** Decompression bomb himoyasi: 10 MB fayl 100 000 × 100 000 px bo'lib ochilishi mumkin. */
    private const MAX_PIXELS = 40_000_000;

    private const DISK = 'local';   // storage/app/private — tashqaridan to'g'ridan-to'g'ri ochilmaydi

    /**
     * Xom baytlarni tekshiradi va toza JPEG qaytaradi.
     *
     * @throws InvalidImageException
     */
    public function sanitize(string $bytes): string
    {
        if ($bytes === '' || strlen($bytes) > config('tryon.upload.max_kb') * 1024) {
            throw new InvalidImageException('Fayl bo\'sh yoki juda katta (10 MB gacha).');
        }

        // MIME — fayl kengaytmasidan emas, baytlarning o'zidan aniqlanadi.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! in_array($mime, config('tryon.upload.allowed_mimes'), true)) {
            throw new InvalidImageException('Faqat JPEG, PNG yoki WebP rasm yuklash mumkin.');
        }

        $size = @getimagesizefromstring($bytes);
        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            throw new InvalidImageException('Rasmni o\'qib bo\'lmadi.');
        }
        if ($size[0] * $size[1] > self::MAX_PIXELS) {
            throw new InvalidImageException('Rasm o\'lchami juda katta.');
        }

        $image = @imagecreatefromstring($bytes);
        if (! $image instanceof GdImage) {
            throw new InvalidImageException('Rasmni o\'qib bo\'lmadi.');
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyExifOrientation($image, $bytes);
        }

        $image = $this->fitWithin($image, (int) config('tryon.upload.max_side'));
        $image = $this->flattenOnWhite($image);   // PNG/WebP shaffofligi → oq fon

        return $this->encodeJpeg($image);
    }

    /** Tozalangan mijoz rasmini private diskka yozadi. Fayl nomi — UUID (foydalanuvchi bergan nom emas). */
    public function storeUpload(string $cleanJpeg): string
    {
        $path = 'uploads/'.Str::uuid()->toString().'.jpg';
        Storage::disk(self::DISK)->put($path, $cleanJpeg);

        return $path;
    }

    /**
     * Provider qaytargan natijani saqlaydi.
     *
     * @throws InvalidImageException
     */
    public function storeResult(string $requestId, string $bytes): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        $ext = match ($mime) {
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => throw new InvalidImageException("Provider rasm qaytarmadi ({$mime})."),
        };

        $path = "results/{$requestId}.{$ext}";
        Storage::disk(self::DISK)->put($path, $bytes);

        return $path;
    }

    public function read(string $path): string
    {
        $bytes = Storage::disk(self::DISK)->get($path);
        if ($bytes === null) {
            throw new InvalidImageException("Fayl topilmadi: {$path}");
        }

        return $bytes;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /** Telefon rasmlari ko'pincha yon tomonga burilgan bo'ladi — EXIF Orientation bo'yicha to'g'rilaymiz. */
    private function applyExifOrientation(GdImage $image, string $bytes): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }

    private function fitWithin(GdImage $image, int $maxSide): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $longest = max($w, $h);

        if ($longest <= $maxSide) {
            return $image;
        }

        $ratio = $maxSide / $longest;
        $scaled = imagescale($image, max(1, (int) round($w * $ratio)), max(1, (int) round($h * $ratio)), IMG_BICUBIC);

        return $scaled instanceof GdImage ? $scaled : $image;
    }

    private function flattenOnWhite(GdImage $image): GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);

        $canvas = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        if ($white === false) {
            return $image;
        }
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $w, $h);

        return $canvas;
    }

    private function encodeJpeg(GdImage $image): string
    {
        ob_start();
        imagejpeg($image, null, (int) config('tryon.upload.jpeg_quality'));

        return (string) ob_get_clean();
    }
}
