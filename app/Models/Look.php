<?php

namespace App\Models;

use App\Enums\ProductType;
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
 * @property bool $is_suit  pidjak va shim bir matodan — tkan ikkalasiga qo'llanadi
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['style_id', 'slug', 'name', 'description', 'is_suit', 'sort'])]
class Look extends Model
{
    protected function casts(): array
    {
        return ['is_suit' => 'boolean'];
    }

    /** Tanlangan tkan qaysi mahsulotlarga tegadi: kostyumda shim + blazer, aks holda faqat shim. */
    public function followsFabric(Product $product): bool
    {
        return $product->type === ProductType::Trousers
            || ($this->is_suit && $product->type === ProductType::Blazer);
    }

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
