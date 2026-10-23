<?php

namespace Tests\Feature\Catalog;

use App\Catalog\CatalogImagePrompts;
use App\Models\Fabric;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** No real OpenAI calls (Http::fake); files go to a temp dir so public/ stays clean. */
class GenerateCatalogImagesTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'sk-test-CATALOG-KEY-1234567890abcdef';

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->dir = sys_get_temp_dir().'/catalog-test-'.uniqid();
        config(['tryon.catalog_media_path' => $this->dir]);
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    public function test_generates_missing_product_and_fabric_images_and_updates_catalog(): void
    {
        Http::fake(['api.openai.com/v1/images/generations' => Http::response([
            'data' => [['b64_json' => base64_encode('fake-webp-bytes')]],
            'usage' => ['input_tokens' => 100, 'input_tokens_details' => ['text_tokens' => 100], 'output_tokens' => 1000],
        ])]);

        $this->artisan('catalog:generate-images', ['--only' => ['tie-green-knit', 'olive-tweed']])
            ->expectsQuestion('OpenAI API key', self::KEY)
            ->assertSuccessful();

        $this->assertFileExists("{$this->dir}/media/catalog/tie-green-knit.webp");
        $this->assertFileExists("{$this->dir}/media/fabrics/swatch-olive-tweed.webp");
        $this->assertSame('media/catalog/tie-green-knit.webp', Product::where('slug', 'tie-green-knit')->value('image'));
        $this->assertSame('media/fabrics/swatch-olive-tweed.webp', Fabric::where('slug', 'olive-tweed')->value('swatch_image'));

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer '.self::KEY)
            && $r['output_format'] === 'webp'
            && str_contains($r['prompt'], 'Pure white seamless background'));
    }

    public function test_dry_run_and_existing_files_send_nothing(): void
    {
        Http::fake();
        File::ensureDirectoryExists("{$this->dir}/media/catalog");
        File::put("{$this->dir}/media/catalog/tie-green-knit.webp", 'x');

        $this->artisan('catalog:generate-images', ['--only' => ['tie-green-knit']])
            ->expectsOutputToContain('All images exist')
            ->assertSuccessful();

        $this->artisan('catalog:generate-images', ['--only' => ['belt-black-leather'], '--dry-run' => true])
            ->expectsOutputToContain('1 image(s) will be generated')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_stops_on_invalid_key_without_spending_on_the_rest(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'invalid_api_key', 'message' => 'bad key']], 401)]);

        $this->artisan('catalog:generate-images', ['--only' => ['tie-green-knit', 'belt-black-leather']])
            ->expectsQuestion('OpenAI API key', self::KEY)
            ->assertFailed();

        Http::assertSentCount(1);
    }

    public function test_prompts_describe_product_with_its_fabric_and_presentation(): void
    {
        $trousers = Product::with('defaultFabric')->where('slug', 'trousers-light-grey-flannel')->firstOrFail();
        $prompt = CatalogImagePrompts::product($trousers);

        $this->assertStringContainsString('light grey flannel fabric', $prompt);
        $this->assertStringContainsString('Laid flat, full length', $prompt);
        $this->assertStringContainsString('No person', $prompt);

        $swatch = CatalogImagePrompts::fabric(Fabric::where('slug', 'olive-tweed')->firstOrFail());
        $this->assertStringContainsString('tweed weave pattern', $swatch);
        $this->assertStringContainsString('#5E5B3C', $swatch);
    }
}
