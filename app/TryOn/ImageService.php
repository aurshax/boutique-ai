<?php

namespace App\TryOn;

use App\TryOn\Exceptions\InvalidImageException;
use GdImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Image handling (plain PHP GD — no extra packages).
 *
 * The customer photo is ALWAYS re-encoded. That at once:
 *  - strips EXIF/GPS and other metadata (personal data),
 *  - destroys any payload hidden inside the file,
 *  - shrinks it (OpenAI input tokens = cost).
 */
class ImageService
{
    /** Decompression-bomb guard: a 10 MB file can decode to 100 000 × 100 000 px. */
    private const MAX_PIXELS = 40_000_000;

    /**
     * Private disk for customer photos and results (config tryon.disk): 'local' = storage/app/private in
     * development; a private S3-compatible bucket in production (container file systems are ephemeral).
     * Never a public disk — images are only reachable through short-lived signed URLs.
     */
    private function disk(): Filesystem
    {
        return Storage::disk((string) config('tryon.disk', 'local'));
    }

    /**
     * Validates raw bytes and returns a clean JPEG.
     *
     * @throws InvalidImageException
     */
    public function sanitize(string $bytes): string
    {
        if ($bytes === '' || strlen($bytes) > config('tryon.upload.max_kb') * 1024) {
            throw new InvalidImageException(__('tryon.image.too_large', ['mb' => (int) round(config('tryon.upload.max_kb') / 1024)]));
        }

        // MIME is detected from the bytes, not from the file extension.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (! in_array($mime, config('tryon.upload.allowed_mimes'), true)) {
            throw new InvalidImageException(__('tryon.image.wrong_type'));
        }

        $size = @getimagesizefromstring($bytes);
        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            throw new InvalidImageException(__('tryon.image.unreadable'));
        }
        if ($size[0] * $size[1] > self::MAX_PIXELS) {
            throw new InvalidImageException(__('tryon.image.too_many_pixels'));
        }

        $image = @imagecreatefromstring($bytes);
        if (! $image instanceof GdImage) {
            throw new InvalidImageException(__('tryon.image.unreadable'));
        }

        if ($mime === 'image/jpeg') {
            $image = $this->applyExifOrientation($image, $bytes);
        }

        $image = $this->fitWithin($image, (int) config('tryon.upload.max_side'));
        $image = $this->flattenOnWhite($image);   // PNG/WebP transparency → white background

        return $this->encodeJpeg($image);
    }

    /** Stores the sanitised customer photo on the private disk. The file name is a UUID, never the user's name. */
    public function storeUpload(string $cleanJpeg): string
    {
        $path = 'uploads/'.Str::uuid()->toString().'.jpg';
        $this->disk()->put($path, $cleanJpeg);

        return $path;
    }

    /**
     * Stores the image returned by the provider.
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
            default => throw new InvalidImageException(__('tryon.image.provider_returned_no_image')),
        };

        $path = "results/{$requestId}.{$ext}";
        $this->disk()->put($path, $bytes);

        return $path;
    }

    public function read(string $path): string
    {
        $bytes = $this->disk()->get($path);
        if ($bytes === null) {
            throw new InvalidImageException(__('tryon.image.missing'));
        }

        return $bytes;
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $this->disk()->exists($path);
    }

    /** Short-lived signed URL (local: Laravel's signed route; S3: a pre-signed URL). */
    public function temporaryUrl(string $path, int $minutes = 10): string
    {
        return $this->disk()->temporaryUrl($path, now()->addMinutes($minutes));
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            $this->disk()->delete($path);
        }
    }

    /** Phone photos are often stored rotated — fix them using the EXIF Orientation tag. */
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
