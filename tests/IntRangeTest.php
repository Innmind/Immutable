<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    IntRange,
    PrimitiveInterface
};
use PHPUnit\Framework\TestCase;

class IntRangeTest extends TestCase
{
    public function testInterface()
    {
        $range = new IntRange(0, 10, 1);

        $this->assertInstanceOf(PrimitiveInterface::class, $range);
        $this->assertSame(0, $range->start());
        $this->assertSame(10, $range->end());
        $this->assertSame(1, $range->step());
        $values = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $this->assertSame($values, $range->toPrimitive());

        foreach ($range as $key => $value) {
            $this->assertSame($values[$key], $value);
        }
    }
}
