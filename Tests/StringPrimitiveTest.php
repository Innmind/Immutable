<?php

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\StringPrimitive;
use Innmind\Immutable\PrimitiveInterface;
use Innmind\Immutable\StringableInterface;

class StringPrimitiveTest extends \PHPUnit_Framework_TestCase
{
    public function testInterfaces()
    {
        $s = new StringPrimitive('foo');

        $this->assertInstanceOf(PrimitiveInterface::class, $s);
        $this->assertInstanceOf(StringableInterface::class, $s);
        $this->assertSame('foo', $s->toPrimitive());
        $this->assertSame('foo', (string) $s);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\TypeException
     * @expectedExceptionMessage Value must be a string
     */
    public function testThrowWhenInvalidType()
    {
        new StringPrimitive(42);
    }
}
