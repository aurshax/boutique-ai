<?php

namespace App\TryOn;

use App\Models\Product;
use App\Models\SizeChart;

/**
 * Bo'y, bel va mijoz aytgan odatdagi razmer bo'yicha katalogdan razmer taklif qiladi.
 * Postgres: WHERE height_cm @> :h AND waist_cm @> :w  (GiST indeks bilan).
 */
class SizeSuggester
{
    public function suggest(Product $product, int $heightCm, ?int $waistCm = null, ?string $usualSize = null): ?string
    {
        $query = SizeChart::where('product_id', $product->id)->forHeight($heightCm);

        if ($waistCm !== null) {
            $query->forWaist($waistCm);
        }

        $sizes = $query->orderBy('id')->pluck('size');

        if ($sizes->isEmpty()) {
            // Bo'y jadvaldan tashqarida — mijoz aytgan razmer katalogda bo'lsa, shuni qaytaramiz.
            $exists = $usualSize !== null
                && SizeChart::where('product_id', $product->id)->where('size', $usualSize)->exists();

            return $exists ? $usualSize : null;
        }

        if ($usualSize !== null && $sizes->contains($usualSize)) {
            return $usualSize;
        }

        // Bir nechta mos keldi: bel ma'lum bo'lsa birinchisi, aks holda o'rtadagisi.
        return $waistCm !== null
            ? $sizes->first()
            : $sizes->get(intdiv($sizes->count(), 2));
    }
}
