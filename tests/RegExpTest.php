<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    RegExp,
    Str,
    Map,
    Exception\DomainException
};
use PHPUnit\Framework\TestCase;

class RegExpTest extends TestCase
{
    public function testInterface()
    {
        $regexp = RegExp::of('/foo/');

        $this->assertSame('/foo/', $regexp->toString());
    }

    public function testOf()
    {
        $regexp = RegExp::of('/foo/');

        $this->assertInstanceOf(RegExp::class, $regexp);
        $this->assertSame('/foo/', $regexp->toString());
    }

    public function testThrowWhenInvalidRegexp()
    {
        $this->expectException(DomainException::class);

        RegExp::of('/foo');
    }

    public function testMatches()
    {
        $regexp = RegExp::of('/^foo/');

        $this->assertTrue($regexp->matches(Str::of('foofoo')));
        $this->assertFalse($regexp->matches(Str::of('barfoo')));
    }

    public function testCapture()
    {
        $regexp = RegExp::of('/(?<i>\d)/');

        $map = $regexp->capture(Str::of('foo123bar'));

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame('1', $this->get($map, 'i')->toString());
    }

    public function get($map, $index)
    {
        return $map->get($index)->match(
            static fn($value) => $value,
            static fn() => null,
        );
    }
}
