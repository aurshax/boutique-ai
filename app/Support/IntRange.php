<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Value object for a PostgreSQL int4range.
 * Both bounds are inclusive here: [min, max].
 * Postgres stores int4range in canonical form: '[170,186)' — upper bound exclusive.
 */
final readonly class IntRange
{
    public function __construct(
        public int $min,
        public int $max,
    ) {
        if ($min > $max) {
            throw new InvalidArgumentException("IntRange: min ({$min}) must not be greater than max ({$max}).");
        }
    }

    public function contains(int $value): bool
    {
        return $value >= $this->min && $value <= $this->max;
    }

    /** Postgres literal: '[170,185]' — Postgres turns it into '[170,186)' itself. */
    public function toPostgres(): string
    {
        return "[{$this->min},{$this->max}]";
    }

    /** Parses forms like '[170,186)', '(169,185]', '[170,185]'. */
    public static function fromPostgres(string $literal): self
    {
        if (! preg_match('/^([\[(])\s*(-?\d+)\s*,\s*(-?\d+)\s*([\])])$/', trim($literal), $m)) {
            throw new InvalidArgumentException("IntRange: invalid literal '{$literal}'.");
        }

        $min = (int) $m[2] + ($m[1] === '(' ? 1 : 0);
        $max = (int) $m[3] - ($m[4] === ')' ? 1 : 0);

        return new self($min, $max);
    }

    public function __toString(): string
    {
        return "{$this->min}–{$this->max}";
    }
}
