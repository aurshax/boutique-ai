<?php

namespace App\Casts;

use App\Support\IntRange;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Eloquent cast: Postgres int4range ↔ App\Support\IntRange.
 *
 * @implements CastsAttributes<IntRange|null, IntRange|array{0:int,1:int}|null>
 */
class IntRangeCast implements CastsAttributes
{
    /** @param  array<string, mixed>  $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?IntRange
    {
        if ($value === null || $value === '' || $value === 'empty') {
            return null;
        }

        return IntRange::fromPostgres((string) $value);
    }

    /** @param  array<string, mixed>  $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) && count($value) === 2) {
            $value = new IntRange((int) $value[0], (int) $value[1]);
        }

        if (! $value instanceof IntRange) {
            throw new InvalidArgumentException("{$key}: IntRange yoki [min, max] massivi kutilgan edi.");
        }

        return $value->toPostgres();
    }
}
