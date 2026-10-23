<?php

namespace Tests\Feature;

use App\Models\Fabric;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_styles_with_from_price_and_fabrics(): void
    {
        $this->withoutVite();
        $this->seed(CatalogSeeder::class);

        // Which fabrics have a swatch depends on the images present in public/media/fabrics
        // (catalog:generate-images), so the expectation is read from the DB, not hard-coded.
        $showcase = Fabric::whereNotNull('swatch_image')->orderBy('id')->limit(3)->get();
        $firstMinExtra = (int) DB::table('product_fabric')->where('fabric_id', $showcase->first()->id)->min('extra_price_uzs');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('home/HomePage')
                ->has('styles', 4)
                ->where('styles.0.name', 'Classic')
                ->where('styles.0.looks_count', 2)
                ->where('styles.0.from_price_uzs', 4_357_000)          // cheapest Classic look
                ->where('styles.0.cover_image', '/media/hero/look-black-suit.webp')
                ->has('fabrics', $showcase->count())                  // only fabrics that have a swatch image
                ->where('fabrics', fn ($fabrics) => collect($fabrics)->pluck('slug')->values()->all() === $showcase->pluck('slug')->all())
                ->where('fabrics.0.swatch_image', fn ($src) => str_starts_with($src, '/media/fabrics/'))
                ->where('fabrics.0.extra_price_uzs', $firstMinExtra));   // cheapest surcharge across products
    }

    public function test_home_works_with_empty_catalog(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('styles', 0)->has('fabrics', 0));
    }
}
