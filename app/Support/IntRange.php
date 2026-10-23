<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * PostgreSQL int4range uchun qiymat obyekti.
 * Ichkarida ikkala chegara ham kiradi (inclusive): [min, max].
 * Postgres int4range ni kanonik ko'rinishda saqlaydi: '[170,186)' — yuqori chegara kirmaydi.
 */
final readonly class IntRange
{
    public function __construct(
        public int $min,
        public int $max,
    ) {
        if ($min > $max) {
            throw new InvalidArgumentException("IntRange: min ({$min}) max ({$max}) dan katta bo'lmasligi kerak.");
        }
    }

    public function contains(int $value): bool
    {
        return $value >= $this->min && $value <= $this->max;
    }

    /** Postgres literal: '[170,185]' — Postgres o'zi '[170,186)' ga aylantiradi. */
    public function toPostgres(): string
    {
        return "[{$this->min},{$this->max}]";
    }

    /** '[170,186)', '(169,185]', '[170,185]' kabi ko'rinishlarni o'qiydi. */
    public static function fromPostgres(string $literal): self
    {
        if (! preg_match('/^([\[(])\s*(-?\d+)\s*,\s*(-?\d+)\s*([\])])$/', trim($literal), $m)) {
            throw new InvalidArgumentException("IntRange: noto'g'ri qiymat '{$literal}'.");
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
