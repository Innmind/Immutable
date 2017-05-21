<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    NumericRange,
    PrimitiveInterface
};
use PHPUnit\Framework\TestCase;

class NumericRangeTest extends TestCase
{
    public function testInterface()
    {
        $range = new NumericRange(0, 10, 1);

        $this->assertInstanceOf(PrimitiveInterface::class, $range);
        $this->assertSame(0.0, $range->start());
        $this->assertSame(10.0, $range->end());
        $this->assertSame(1.0, $range->step());
        $values = [0.0, 1.0, 2.0, 3.0, 4.0, 5.0, 6.0, 7.0, 8.0, 9.0, 10.0];
        $this->assertSame($values, $range->toPrimitive());

        foreach ($range as $key => $value) {
            $this->assertSame($values[$key], $value);
        }
    }
}
