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
        $regexp = new RegExp('/foo/');

        $this->assertSame('/foo/', (string) $regexp);
    }

    public function testOf()
    {
        $regexp = RegExp::of('/foo/');

        $this->assertInstanceOf(RegExp::class, $regexp);
        $this->assertSame('/foo/', (string) $regexp);
    }

    public function testThrowWhenInvalidRegexp()
    {
        $this->expectException(DomainException::class);

        new RegExp('/foo');
    }

    public function testMatches()
    {
        $regexp = new RegExp('/^foo/');

        $this->assertTrue($regexp->matches(Str::of('foofoo')));
        $this->assertFalse($regexp->matches(Str::of('barfoo')));
    }

    public function testCapture()
    {
        $regexp = new RegExp('/(?<i>\d)/');

        $map = $regexp->capture(Str::of('foo123bar'));

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame('scalar', (string) $map->keyType());
        $this->assertSame(Str::class, (string) $map->valueType());
        $this->assertSame('1', (string) $map->get('i'));
    }
}
