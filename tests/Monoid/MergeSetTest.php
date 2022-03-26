<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\MergeSet,
    Monoid,
    Set,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set as DataSet,
};
use Fixtures\Innmind\Immutable\Set as FSet;
use Properties\Innmind\Immutable\Monoid as PMonoid;

class MergeSetTest extends TestCase
{
    use BlackBox;

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

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(static function($property) {
                $property->ensureHeldBy(MergeSet::of());
            });
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(PMonoid::properties($this->set(), $this->equals()))
            ->then(static function($properties) {
                $properties->ensureHeldBy(MergeSet::of());
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

    private function set(): DataSet
    {
        return FSet::of(
            DataSet\AnyType::any(),
            DataSet\Integers::between(1, 10),
        );
    }
}
