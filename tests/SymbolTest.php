<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Symbol,
    PrimitiveInterface
};

class SymbolTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $s = new Symbol('foo');

        $this->assertInstanceOf(PrimitiveInterface::class, $s);
        $this->assertSame('foo', $s->toPrimitive());
        $this->assertSame('foo', (string) $s);

        $this->assertSame(42, (new Symbol(42))->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage A Symbol can be composed only of an int or a string
     */
    public function testThrowWhenInvalidPrimitiveUsed()
    {
        new Symbol(42.0);
    }
}
