<?php

namespace App\Models;

use App\Casts\IntRangeCast;
use App\Support\IntRange;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Product size chart: height and waist range per size (Postgres int4range).
 *
 * @property int $id
 * @property int $product_id
 * @property string $size
 * @property IntRange $height_cm
 * @property IntRange|null $waist_cm
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['product_id', 'size', 'height_cm', 'waist_cm'])]
class SizeChart extends Model
{
    protected function casts(): array
    {
        return [
            'height_cm' => IntRangeCast::class,
            'waist_cm' => IntRangeCast::class,
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Sizes that fit a height (uses the GiST index): WHERE height_cm @> 178
     *
     * @param  Builder<SizeChart>  $query
     * @return Builder<SizeChart>
     */
    public function scopeForHeight(Builder $query, int $heightCm): Builder
    {
        return $query->whereRaw('height_cm @> ?::int', [$heightCm]);
    }

    /**
     * Sizes that fit a waist measurement.
     *
     * @param  Builder<SizeChart>  $query
     * @return Builder<SizeChart>
     */
    public function scopeForWaist(Builder $query, int $waistCm): Builder
    {
        return $query->whereRaw('waist_cm @> ?::int', [$waistCm]);
    }
}
