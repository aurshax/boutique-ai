<?php

namespace App\Http\Controllers;

use App\Models\Fabric;
use App\Models\Look;
use App\Models\Style;
use Inertia\Inertia;
use Inertia\Response;

/** Home page: styles (look count, cheapest look) and the fabric showcase — all from the catalogue, nothing hard-coded. */
class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $styles = Style::with('looks.products')
            ->orderBy('sort')
            ->get()
            ->map(fn (Style $style) => [
                'slug' => $style->slug,
                'name' => $style->name,
                'description' => $style->description,
                'cover_image' => $style->cover_image ? '/'.$style->cover_image : null,
                'looks_count' => $style->looks->count(),
                'from_price_uzs' => $style->looks->map(fn (Look $look) => $look->totalPriceUzs())->min(),
            ]);

        $fabrics = Fabric::query()
            ->whereNotNull('swatch_image')
            ->withMin('products as min_extra_uzs', 'product_fabric.extra_price_uzs')
            ->orderBy('id')
            ->limit(3)                       // the Home showcase is designed for 3 cards (FabricShowcase)
            ->get()
            ->map(fn (Fabric $fabric) => [
                'slug' => $fabric->slug,
                'name' => $fabric->name,
                'composition' => $fabric->composition,
                'swatch_image' => '/'.$fabric->swatch_image,
                'extra_price_uzs' => (int) ($fabric->getAttribute('min_extra_uzs') ?? 0),
            ]);

        return Inertia::render('home/HomePage', [
            'styles' => $styles,
            'fabrics' => $fabrics,
            'estimateUsd' => config('tryon.estimate_usd'),
        ]);
    }
}
