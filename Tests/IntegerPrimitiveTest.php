<?php

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\IntegerPrimitive;
use Innmind\Immutable\PrimitiveInterface;

class IntegerPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    public function testInterfaces()
    {
        $s = new IntegerPrimitive(42);

        $this->assertInstanceOf(PrimitiveInterface::class, $s);
        $this->assertSame(42, $s->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\TypeException
     * @expectedExceptionMessage Value must be an integer
     */
    public function testThrowWhenInvalidType()
    {
        new IntegerPrimitive('42');
    }
}
