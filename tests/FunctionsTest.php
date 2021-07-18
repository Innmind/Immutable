<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use function Innmind\Immutable\join;
use Innmind\Immutable\{
    Set,
    Sequence,
    Map,
    Str,
};
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
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
}
