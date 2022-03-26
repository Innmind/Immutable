<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\MergeMap,
    Monoid,
    Map,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Immutable\Map as FMap;
use Properties\Innmind\Immutable\Monoid as PMonoid;

class MergeMapTest extends TestCase
{
    use BlackBox;

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

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(static function($property) {
                $property->ensureHeldBy(MergeMap::of());
            });
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(PMonoid::properties($this->set(), $this->equals()))
            ->then(static function($properties) {
                $properties->ensureHeldBy(MergeMap::of());
            });
    }

    public function properties(): iterable
    {
        foreach (PMonoid::list($this->set(), $this->equals()) as $property) {
            yield [$property];
        }
    }

    public function equals(): callable
    {
        return static fn($a, $b) => $a->equals($b);
    }

    private function set(): Set
    {
        return FMap::of(
            Set\Integers::between(0, 200),
            Set\Integers::between(0, 200),
            Set\Integers::between(1, 10),
        );
    }
}
