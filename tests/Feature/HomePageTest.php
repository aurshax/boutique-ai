<?php

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_styles_with_from_price_and_fabrics(): void
    {
        $this->withoutVite();
        $this->seed(CatalogSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->has('styles', 4)
                ->where('styles.0.name', 'Classic')
                ->where('styles.0.looks_count', 2)
                ->where('styles.0.from_price_uzs', 4_357_000)          // eng arzon Classic look
                ->where('styles.0.cover_image', '/media/hero/look-black-suit.webp')
                ->has('fabrics', 3)                                   // faqat swatch rasmi bor tkanlar
                ->where('fabrics.0.slug', 'grey-check')
                ->where('fabrics.0.extra_price_uzs', 130_000));
    }

    public function test_home_works_with_empty_catalog(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('styles', 0)->has('fabrics', 0));
    }
}
