<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Catalogue product: trousers, shirt, blazer, belt, tie, shoes.
 *
 * @property int $id
 * @property string $slug
 * @property ProductType $type
 * @property string $name
 * @property string|null $description  English visual description (image generation and try-on prompt)
 * @property int $price_uzs
 * @property string|null $image
 * @property int|null $default_fabric_id
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'type', 'name', 'description', 'price_uzs', 'image', 'default_fabric_id', 'is_active'])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'price_uzs' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Fabric, $this> */
    public function defaultFabric(): BelongsTo
    {
        return $this->belongsTo(Fabric::class, 'default_fabric_id');
    }

    /** Fabrics this product can be made in. @return BelongsToMany<Fabric, $this> */
    public function fabrics(): BelongsToMany
    {
        return $this->belongsToMany(Fabric::class, 'product_fabric')->withPivot('extra_price_uzs');
    }

    /** @return HasMany<SizeChart, $this> */
    public function sizeCharts(): HasMany
    {
        return $this->hasMany(SizeChart::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
