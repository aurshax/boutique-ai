<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Uslub: Classic, Office, Smart Casual ...
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $cover_image
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['slug', 'name', 'description', 'cover_image', 'sort'])]
class Style extends Model
{
    /** @return HasMany<Look, $this> */
    public function looks(): HasMany
    {
        return $this->hasMany(Look::class)->orderBy('sort');
    }
}
