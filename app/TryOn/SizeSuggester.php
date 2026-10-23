<?php

namespace App\TryOn;

use App\Models\Product;
use App\Models\SizeChart;

/**
 * Suggests a size from the catalogue by height, waist and the customer's usual size.
 * Postgres: WHERE height_cm @> :h AND waist_cm @> :w  (GiST index).
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
            // Height is outside the chart — fall back to the customer's usual size if the catalogue has it.
            $exists = $usualSize !== null
                && SizeChart::where('product_id', $product->id)->where('size', $usualSize)->exists();

            return $exists ? $usualSize : null;
        }

        if ($usualSize !== null && $sizes->contains($usualSize)) {
            return $usualSize;
        }

        // Several sizes fit: take the first when the waist is known, otherwise the middle one.
        return $waistCm !== null
            ? $sizes->first()
            : $sizes->get(intdiv($sizes->count(), 2));
    }
}
