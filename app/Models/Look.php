<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Tayyor obraz (look): shim + ko'ylak/blazer + aksessuarlar.
 *
 * @property int $id
 * @property int $style_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['style_id', 'slug', 'name', 'description', 'sort'])]
class Look extends Model
{
    /** @return BelongsTo<Style, $this> */
    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    /** @return BelongsToMany<Product, $this> */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'look_items')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /** Look'ning umumiy narxi (so'm), tkan ustamasisiz. */
    public function totalPriceUzs(): int
    {
        return (int) $this->products->sum('price_uzs');
    }
}
