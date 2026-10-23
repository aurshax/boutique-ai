<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A ready-made outfit (look): trousers + shirt/blazer + accessories.
 *
 * @property int $id
 * @property int $style_id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property bool $is_suit jacket and trousers share one fabric — the chosen fabric applies to both
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

    /** Which products the chosen fabric applies to: trousers + blazer in a suit, otherwise trousers only. */
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

    /** Look total price (UZS) without fabric surcharges. */
    public function totalPriceUzs(): int
    {
        return (int) $this->products->sum('price_uzs');
    }
}
