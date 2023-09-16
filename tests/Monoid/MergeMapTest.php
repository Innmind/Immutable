<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\MergeMap,
    Monoid,
    Map,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class MergeMapTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Monoid::class, MergeMap::of());
    }

    public function testCombine()
    {
        $map = MergeMap::of()->combine(
            Map::of([1, 3]),
            Map::of([2, 4]),
        );

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame(
            [1, 2],
            $map->keys()->toList(),
        );
        $this->assertSame(
            [3, 4],
            $map->values()->toList(),
        );
    }
}
