<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use function Innmind\Immutable\{
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

    public function testJoinSet()
    {
        $str = join('|', Set::of('1', '2', '3'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }

    public function testJoinSequence()
    {
        $str = join('|', Sequence::of('1', '2', '3'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2|3', $str->toString());
    }

    public function testThrowWhenTryingToAccessFirstValueOfAnEmptySet()
    {
        $this->expectException(EmptySet::class);

        first(Set::of());
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
