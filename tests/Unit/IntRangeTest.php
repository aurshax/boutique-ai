<?php

namespace Tests\Unit;

use App\Support\IntRange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IntRangeTest extends TestCase
{
    public function test_parses_postgres_canonical_form(): void
    {
        $range = IntRange::fromPostgres('[170,186)');

        $this->assertSame(170, $range->min);
        $this->assertSame(185, $range->max);
    }

    public function test_parses_exclusive_lower_and_inclusive_upper(): void
    {
        $range = IntRange::fromPostgres('(169,185]');

        $this->assertSame(170, $range->min);
        $this->assertSame(185, $range->max);
    }

    public function test_contains_is_inclusive(): void
    {
        $range = new IntRange(170, 185);

        $this->assertTrue($range->contains(170));
        $this->assertTrue($range->contains(185));
        $this->assertFalse($range->contains(186));
    }

    public function test_rejects_min_greater_than_max(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new IntRange(190, 170);
    }

    public function test_rejects_garbage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        IntRange::fromPostgres('170-185');
    }
}
