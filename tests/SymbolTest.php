<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Symbol,
    PrimitiveInterface,
    Exception\InvalidArgumentException
};
use PHPUnit\Framework\TestCase;

class SymbolTest extends TestCase
{
    public function testInterface()
    {
        $s = new Symbol('foo');

        $this->assertSame('foo', $s->value());
        $this->assertSame('foo', (string) $s);

        $this->assertSame(42, (new Symbol(42))->value());
    }

    public function testThrowWhenInvalidPrimitiveUsed()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A Symbol can be composed only of an int or a string');

        new Symbol(42.0);
    }
}
