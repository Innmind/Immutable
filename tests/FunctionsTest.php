<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use function Innmind\Immutable\{
    assertSet,
    assertSequence,
    assertMap,
    unwrap,
    join,
    first,
};
use Innmind\Immutable\{
    Set,
    Sequence,
    Map,
    Str,
    Exception\EmptySet,
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

    public function testAssertSequence()
    {
        $this->assertNull(assertSequence('string', Sequence::of('string'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Sequence<string>, Sequence<int> given');

        assertSequence('string', Sequence::of('int'), 42);
    }

    public function testAssertMap()
    {
        $this->assertNull(assertMap('string', 'int', Map::of('string', 'int'), 42));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 42 must be of type Map<string, int>, Map<string, string> given');

        assertMap('string', 'int', Map::of('string', 'string'), 42);
    }

    public function testUnwrapSet()
    {
        $this->assertSame(
            [1, 2, 3],
            unwrap(Set::ints(1, 2, 3)),
        );
    }

    public function testUnwrapSequence()
    {
        $this->assertSame(
            [1, 2, 3],
            unwrap(Sequence::ints(1, 2, 3)),
        );
    }

    public function testThrowWhenUnwrappingNotOfExpectedType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Set|Sequence, stdClass given');

        unwrap(new \stdClass);
    }

    public function testThrowWhenJoiningNotOfExpectedType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Set|Sequence, stdClass given');

        join('|', new \stdClass);
    }

    public function testThrowWhenJoiningOnNonStringSet()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Set<string>, Set<int> given');

        join('|', Set::of('int'));
    }

    public function testThrowWhenJoiningOnNonStringSequence()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Sequence<string>, Sequence<int> given');

        join('|', Sequence::of('int'));
    }

    public function testJoinSet()
    {
        $str = join('|', Set::of('string', '1', '2', '3'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }

    public function testJoinSequence()
    {
        $str = join('|', Sequence::of('string', '1', '2', '3'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }

    public function testThrowWhenTryingToAccessFirstValueOfAnEmptySet()
    {
        $this->expectException(EmptySet::class);

        first(Set::of('int'));
    }

    public function testAccessFirstValueOfASet()
    {
        $this->assertSame(null, first(Set::mixed(null, 1, '')));
        $this->assertSame('', first(Set::mixed('', 1, null)));
        $this->assertSame(false, first(Set::mixed(false, 1, null)));
        $this->assertSame(0, first(Set::mixed(0, 1, null)));
        $this->assertSame(42, first(Set::mixed(42, 1, null)));
    }
}
