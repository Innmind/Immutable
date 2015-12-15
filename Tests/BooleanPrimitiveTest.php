<?php

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\BooleanPrimitive;
use Innmind\Immutable\PrimitiveInterface;

class BooleanPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    public function testInterfaces()
    {
        $s = new BooleanPrimitive(true);

        $this->assertInstanceOf(PrimitiveInterface::class, $s);
        $this->assertSame(true, $s->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\TypeException
     * @expectedExceptionMessage Value must be a boolean
     */
    public function testThrowWhenInvalidType()
    {
        new BooleanPrimitive(42);
    }
}
