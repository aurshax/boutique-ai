<?php

namespace App\Models;

use App\Enums\FabricPattern;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Fabric (cloth) a garment can be made of.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $composition
 * @property FabricPattern $pattern
 * @property string $color_hex
 * @property string|null $swatch_image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'name', 'composition', 'pattern', 'color_hex', 'swatch_image'])]
class Fabric extends Model
{
    protected function casts(): array
    {
        return [
            'pattern' => FabricPattern::class,
        ];
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_fabric')->withPivot('extra_price_uzs');
    }
}
