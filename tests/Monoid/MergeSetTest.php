<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\MergeSet,
    Monoid,
    Set,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class MergeSetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Monoid::class, MergeSet::of());
    }

    public function testCombine()
    {
        $set = MergeSet::of()->combine(
            Set::of(1, 3),
            Set::of(2, 4, 3),
        );

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(
            [1, 3, 2, 4],
            $set->toList(),
        );
    }
}
