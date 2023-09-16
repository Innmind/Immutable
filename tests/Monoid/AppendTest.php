<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\Append,
    Monoid,
    Sequence,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class AppendTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Monoid::class, Append::of());
    }

    public function testCombine()
    {
        $sequence = Append::of()->combine(
            Sequence::of(1, 3),
            Sequence::of(2, 4),
        );

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(
            [1, 3, 2, 4],
            $sequence->toList(),
        );
    }
}
