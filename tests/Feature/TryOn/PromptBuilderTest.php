<?php

namespace Tests\Feature\TryOn;

use App\Models\Fabric;
use App\Models\Look;
use App\Models\Product;
use App\TryOn\Data\ReferenceImage;
use App\TryOn\PromptBuilder;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Results must not depend on images on disk: they live in a temp dir (catalog_media_path). */
class PromptBuilderTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->dir = sys_get_temp_dir().'/prompt-test-'.uniqid();
        config(['tryon.catalog_media_path' => $this->dir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_without_catalog_images_uses_text_descriptions(): void
    {
        $built = $this->build();

        $this->assertStringContainsString('178 cm', $built['prompt']);
        $this->assertStringContainsString('Grey check', $built['prompt']);
        $this->assertStringContainsString('trousers: navy blue wool suit trousers', $built['prompt']);   // inglizcha tavsif
        $this->assertStringContainsString('Keep his face', $built['prompt']);
        $this->assertStringContainsString('Do not slim', $built['prompt']);                                 // v3
        $this->assertCount(0, $built['references']);
        $this->assertStringNotContainsString('see image', $built['prompt']);
    }

    public function test_suit_look_applies_fabric_to_jacket_and_trousers(): void
    {
        $prompt = $this->build()['prompt'];   // smart-navy-knit — kostyum

        $this->assertStringContainsString('matching suit', $prompt);
        $this->assertMatchesRegularExpression('/- blazer: .*\(in Grey check/', $prompt);
        $this->assertMatchesRegularExpression('/- trousers: .*\(in Grey check/', $prompt);
        $this->assertStringContainsString('cut and style only', $prompt);
    }

    public function test_separates_look_applies_fabric_to_trousers_only(): void
    {
        $look = Look::where('slug', 'smart-herringbone')->firstOrFail();
        $prompt = (new PromptBuilder)->build($look, Fabric::where('slug', 'grey-check')->firstOrFail(), 178)['prompt'];

        $this->assertStringContainsString('The trousers must be made of this fabric', $prompt);
        $this->assertStringNotContainsString('matching suit', $prompt);
        $this->assertDoesNotMatchRegularExpression('/- blazer: .*\(in /', $prompt);
    }

    public function test_catalog_images_become_numbered_references_after_the_person(): void
    {
        $look = Look::where('slug', 'smart-navy-knit')->with('products')->firstOrFail();
        foreach ($look->products as $product) {
            $this->fakeFile("media/catalog/{$product->slug}.webp");
            Product::whereKey($product->id)->update(['image' => "media/catalog/{$product->slug}.webp"]);
        }
        $this->fakeFile('media/fabrics/swatch-grey-check.webp');
        Fabric::where('slug', 'grey-check')->update(['swatch_image' => 'media/fabrics/swatch-grey-check.webp']);

        $built = $this->build();

        $count = $look->products->count();
        $this->assertCount($count + 1, $built['references']);
        $this->assertSame('trousers', $built['references'][0]->label);
        $this->assertSame('fabric swatch', $built['references'][$count]->label);
        $this->assertStringContainsString('(see image 2)', $built['prompt']);            // image 1 is the customer
        $this->assertStringContainsString('fabric swatch in image '.($count + 2), $built['prompt']);
    }

    public function test_path_traversal_in_image_path_is_ignored(): void
    {
        $this->fakeFile('outside.webp');   // exists in the temp dir, but the path uses '..'
        Product::where('slug', 'trousers-navy-classic')->update(['image' => 'media/../outside.webp']);

        $this->assertCount(0, $this->build()['references']);
    }

    /** @return array{prompt: string, references: list<ReferenceImage>} */
    private function build(): array
    {
        $look = Look::where('slug', 'smart-navy-knit')->firstOrFail();
        $fabric = Fabric::where('slug', 'grey-check')->firstOrFail();

        return (new PromptBuilder)->build($look, $fabric, 178);
    }

    private function fakeFile(string $relative): void
    {
        File::ensureDirectoryExists(dirname("{$this->dir}/{$relative}"));
        File::put("{$this->dir}/{$relative}", 'image-bytes');
    }
}
