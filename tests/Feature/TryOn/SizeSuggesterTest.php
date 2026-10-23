<?php

namespace Tests\Feature\TryOn;

use App\Models\Product;
use App\TryOn\SizeSuggester;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SizeSuggesterTest extends TestCase
{
    use RefreshDatabase;

    private Product $trousers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
        $this->trousers = Product::where('slug', 'trousers-navy-classic')->firstOrFail();
    }

    public function test_height_and_waist_give_exact_size(): void
    {
        $this->assertSame('48', (new SizeSuggester)->suggest($this->trousers, 178, 84));
    }

    public function test_usual_size_wins_when_it_fits_height(): void
    {
        $this->assertSame('52', (new SizeSuggester)->suggest($this->trousers, 178, null, '52'));
    }

    public function test_height_only_returns_middle_candidate(): void
    {
        // 178 sm: 46, 48, 50, 52, 54, 56 mos keladi (6 ta) → intdiv(6, 2) = 3-indeks → 52
        $this->assertSame('52', (new SizeSuggester)->suggest($this->trousers, 178));
    }

    public function test_out_of_chart_height_falls_back_to_usual_size(): void
    {
        $this->assertSame('56', (new SizeSuggester)->suggest($this->trousers, 225, null, '56'));
        $this->assertNull((new SizeSuggester)->suggest($this->trousers, 225));
    }
}
