<?php

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\FloatPrimitive;
use Innmind\Immutable\PrimitiveInterface;

class FloatPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    public function testInterfaces()
    {
        $s = new FloatPrimitive(42.0);

        $this->assertInstanceOf(PrimitiveInterface::class, $s);
        $this->assertSame(42.0, $s->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\TypeException
     * @expectedExceptionMessage Value must be a float
     */
    public function testThrowWhenInvalidType()
    {
        new FloatPrimitive('42');
    }
}
