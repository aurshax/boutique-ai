<?php

namespace Tests\Feature\TryOn;

use App\TryOn\Exceptions\InvalidImageException;
use App\TryOn\ImageService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    private ImageService $images;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->images = new ImageService;
    }

    public function test_large_image_is_scaled_to_max_side(): void
    {
        $clean = $this->images->sanitize($this->jpeg(3000, 2000));

        [$w, $h] = getimagesizefromstring($clean) ?: [0, 0];
        $max = (int) config('tryon.upload.max_side');
        $this->assertSame($max, max($w, $h));
        $this->assertSame((int) round($max * 2000 / 3000), min($w, $h));
    }

    public function test_output_is_always_jpeg_even_for_png_input(): void
    {
        $clean = $this->images->sanitize($this->png(400, 600));

        $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->buffer($clean));
    }

    public function test_exif_metadata_is_stripped(): void
    {
        $withExif = $this->injectExif($this->jpeg(200, 300), 'GPS-SECRET-41.3111,69.2797');
        $this->assertStringContainsString('GPS-SECRET', $withExif);

        $clean = $this->images->sanitize($withExif);

        $this->assertStringNotContainsString('GPS-SECRET', $clean);
        $this->assertStringNotContainsString('Exif', $clean);
    }

    public function test_rejects_non_image_even_with_image_extension(): void
    {
        $this->expectException(InvalidImageException::class);
        $this->images->sanitize("<?php echo 'hack'; ?>");
    }

    public function test_rejects_gif(): void
    {
        $gd = imagecreatetruecolor(10, 10);
        ob_start();
        imagegif($gd);
        $gif = (string) ob_get_clean();

        $this->expectException(InvalidImageException::class);
        $this->images->sanitize($gif);
    }

    public function test_upload_is_stored_on_private_disk_with_uuid_name(): void
    {
        $path = $this->images->storeUpload($this->images->sanitize($this->jpeg(100, 100)));

        $this->assertMatchesRegularExpression('#^uploads/[0-9a-f-]{36}\.jpg$#', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_store_result_rejects_non_image(): void
    {
        $this->expectException(InvalidImageException::class);
        $this->images->storeResult('abc', 'not an image');
    }

    private function jpeg(int $w, int $h): string
    {
        $gd = imagecreatetruecolor($w, $h);
        ob_start();
        imagejpeg($gd);

        return (string) ob_get_clean();
    }

    private function png(int $w, int $h): string
    {
        $gd = imagecreatetruecolor($w, $h);
        imagesavealpha($gd, true);
        $transparent = imagecolorallocatealpha($gd, 0, 0, 0, 127);
        if ($transparent !== false) {
            imagefill($gd, 0, 0, $transparent);
        }
        ob_start();
        imagepng($gd);

        return (string) ob_get_clean();
    }

    /** Inserts a fake APP1 (Exif) segment after the JPEG SOI marker — mimics a phone photo. */
    private function injectExif(string $jpeg, string $secret): string
    {
        $payload = "Exif\0\0".'II*'."\0".$secret;
        $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

        return substr($jpeg, 0, 2).$segment.substr($jpeg, 2);
    }
}
