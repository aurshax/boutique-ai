<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductType;
use App\Models\Fabric;
use App\Models\Look;
use App\Models\Product;
use App\Models\SizeChart;
use App\Models\Style;
use App\Models\TryOnRequest;
use App\Support\IntRange;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_seeder_creates_test_catalog(): void
    {
        $this->assertSame(4, Style::count());
        $this->assertSame(10, Look::count());
        $this->assertSame(8, Fabric::count());
        $this->assertSame(22, Product::count());
        // Visual description — used for image generation and the try-on prompt
        $this->assertSame(0, Product::whereNull('description')->count());
    }

    public function test_suit_looks_share_fabric_between_jacket_and_trousers(): void
    {
        $suit = Look::where('slug', 'classic-navy-suit')->with('products')->firstOrFail();
        $separates = Look::where('slug', 'smart-herringbone')->with('products')->firstOrFail();

        $this->assertTrue($suit->is_suit);
        $this->assertFalse($separates->is_suit);
        $blazer = fn (Look $look) => $look->products->first(fn ($p) => $p->type === ProductType::Blazer);
        $this->assertTrue($suit->followsFabric($blazer($suit)));
        $this->assertFalse($separates->followsFabric($blazer($separates)));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(22, Product::count());
        $this->assertSame(10, Look::count());
    }

    public function test_look_products_are_ordered_and_total_price_is_summed(): void
    {
        $look = Look::where('slug', 'smart-navy-knit')->with('products')->firstOrFail();

        $this->assertSame(ProductType::Trousers, $look->products->first()?->type);
        $this->assertSame(690_000 + 1_890_000 + 429_000 + 269_000 + 890_000, $look->totalPriceUzs());
    }

    public function test_size_chart_range_round_trips_through_int4range(): void
    {
        $chart = SizeChart::whereHas('product', fn ($q) => $q->where('slug', 'trousers-navy-classic'))
            ->where('size', '48')
            ->firstOrFail();

        $this->assertEquals(new IntRange(168, 182), $chart->height_cm);
        $this->assertEquals(new IntRange(81, 86), $chart->waist_cm);

        // Postgres canonical form: the upper bound is exclusive.
        $raw = DB::table('size_charts')->where('id', $chart->id)->value('height_cm');
        $this->assertSame('[168,183)', $raw);
    }

    public function test_size_suggestion_by_height_and_waist(): void
    {
        $product = Product::where('slug', 'trousers-navy-classic')->firstOrFail();

        $sizes = SizeChart::where('product_id', $product->id)
            ->forHeight(178)
            ->forWaist(84)
            ->pluck('size')
            ->all();

        $this->assertSame(['48'], $sizes);
    }

    public function test_only_trousers_and_blazers_support_fabric_swap(): void
    {
        $this->assertSame(7, Product::where('slug', 'trousers-navy-classic')->firstOrFail()->fabrics()->count());
        $this->assertSame(0, Product::where('slug', 'tie-burgundy-silk')->firstOrFail()->fabrics()->count());
    }

    public function test_postgres_rejects_invalid_product_type(): void
    {
        $this->expectException(QueryException::class);

        DB::table('products')->insert([
            'slug' => 'bad', 'type' => 'hat', 'name' => 'Shlyapa', 'price_uzs' => 1,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_postgres_rejects_out_of_range_height(): void
    {
        $this->expectException(QueryException::class);

        TryOnRequest::create([
            'height_cm' => 300,
            'prompt_version' => 'v1',
            'provider' => 'mock',
        ]);
    }

    public function test_try_on_request_uses_uuid_and_defaults(): void
    {
        $request = TryOnRequest::create([
            'height_cm' => 178,
            'prompt_version' => 'v1',
            'provider' => 'mock',
            'params' => ['quality' => 'medium'],
        ]);

        $this->assertTrue(Str::isUuid($request->id));
        $this->assertSame('queued', $request->fresh()?->status->value);

        // jsonb containment (served by the GIN index)
        $this->assertSame(1, TryOnRequest::whereRaw("params @> '{\"quality\":\"medium\"}'::jsonb")->count());
    }
}
