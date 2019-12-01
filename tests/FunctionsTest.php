<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use function Innmind\Immutable\{
    assertSet,
    assertStream,
    assertMap
};
use Innmind\Immutable\{
    Set,
    Stream,
    Map
};
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testAssertSet()
    {
        $this->assertNull(assertSet('string', Set::of('string'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Set<string>, Set<int> given');

        assertSet('string', Set::of('int'), 42);
    }

    public function testAssertStream()
    {
        $this->assertNull(assertStream('string', Stream::of('string'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Stream<string>, Stream<int> given');

        assertStream('string', Stream::of('int'), 42);
    }

    public function testAssertMap()
    {
        $this->assertNull(assertMap('string', 'int', Map::of('string', 'int'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Map<string, int>, Map<string, string> given');

        assertMap('string', 'int', Map::of('string', 'string'), 42);
    }
}
