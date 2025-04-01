<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Set;

use Innmind\Immutable\{
    Set,
    Map,
    Sequence,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Set::class,
            Set::of(),
        );
    }

    public function testSize()
    {
        $this->assertSame(2, Set::of(1, 2)->size());
        $this->assertSame(2, Set::of(1, 2)->count());
    }

    public function testIterator()
    {
        $this->assertSame([1, 2], Set::of(1, 2)->unsorted()->toList());
    }

    public function testIntersect()
    {
        $a = Set::of(1, 2);
        $b = Set::of(2, 3);
        $c = $a->intersect($b);

        $this->assertSame([1, 2], $a->unsorted()->toList());
        $this->assertSame([2, 3], $b->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $c);
        $this->assertSame([2], $c->unsorted()->toList());
    }

    public function testAdd()
    {
        $a = Set::of(1);
        $b = ($a)(2);

        $this->assertSame([1], $a->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $b);
        $this->assertSame([1, 2], $b->unsorted()->toList());
        $this->assertTrue($b->equals(($b)(2)));
    }

    public function testContains()
    {
        $set = Set::of(1);

        $this->assertTrue($set->contains(1));
        $this->assertFalse($set->contains(2));
    }

    public function testRemove()
    {
        $a = Set::of(1, 2, 3, 4);
        $b = $a->remove(3);

        $this->assertSame([1, 2, 3, 4], $a->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $b);
        $this->assertSame([1, 2, 4], $b->unsorted()->toList());
        $this->assertTrue($a->equals($a->remove(5)));
    }

    public function testDiff()
    {
        $a = Set::of(1, 2, 3);
        $b = Set::of(2, 4);
        $c = $a->diff($b);

        $this->assertSame([1, 2, 3], $a->unsorted()->toList());
        $this->assertSame([2, 4], $b->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $c);
        $this->assertSame([1, 3], $c->unsorted()->toList());
    }

    public function testEquals()
    {
        $this->assertTrue((Set::of(1, 2))->equals(Set::of(1, 2)));
        $this->assertFalse((Set::of(1, 2))->equals(Set::of(1)));
        $this->assertFalse((Set::of(1, 2))->equals(Set::of(1, 2, 3)));
    }

    public function testFilter()
    {
        $a = Set::of(1, 2, 3, 4);
        $b = $a->filter(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], $a->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $b);
        $this->assertSame([2, 4], $b->unsorted()->toList());
    }

    public function testForeach()
    {
        $set = Set::of(1, 2, 3, 4);
        $calls = 0;
        $sum = 0;

        $this->assertInstanceOf(
            SideEffect::class,
            $set->foreach(static function($i) use (&$calls, &$sum) {
                ++$calls;
                $sum += $i;
            }),
        );

        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testGroupBy()
    {
        $set = Set::of(1, 2, 3, 4);
        $groups = $set->groupBy(static fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], $set->unsorted()->toList());
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], $this->get($groups, 0)->toList());
        $this->assertSame([1, 3], $this->get($groups, 1)->toList());
    }

    public function testMap()
    {
        $a = Set::of(1, 2, 3);
        $b = $a->map(static fn($i) => $i * 2);

        $this->assertSame([1, 2, 3], $a->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $b);
        $this->assertSame([2, 4, 6], $b->unsorted()->toList());
    }

    public function testPartition()
    {
        $set = Set::of(1, 2, 3, 4);
        $groups = $set->partition(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], $set->unsorted()->toList());
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], $this->get($groups, true)->toList());
        $this->assertSame([1, 3], $this->get($groups, false)->toList());
    }

    public function testSort()
    {
        $set = Set::of(1, 4, 3, 2);
        $sorted = $set->sort(static fn($a, $b) => $a > $b ? 1 : -1);

        $this->assertSame([1, 4, 3, 2], $set->unsorted()->toList());
        $this->assertInstanceOf(Sequence::class, $sorted);
        $this->assertSame([1, 2, 3, 4], $sorted->toList());
    }

    public function testMerge()
    {
        $a = Set::of(1, 2);
        $b = Set::of(2, 3);
        $c = $a->merge($b);

        $this->assertSame([1, 2], $a->unsorted()->toList());
        $this->assertSame([2, 3], $b->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $c);
        $this->assertSame([1, 2, 3], $c->unsorted()->toList());
    }

    public function testReduce()
    {
        $set = Set::of(1, 2, 3, 4);

        $this->assertSame(10, $set->reduce(0, static fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $a = Set::of(1);
        $b = $a->clear();

        $this->assertSame([1], $a->unsorted()->toList());
        $this->assertInstanceOf(Set::class, $b);
        $this->assertSame([], $b->unsorted()->toList());
    }

    public function testEmpty()
    {
        $this->assertTrue((Set::of())->empty());
        $this->assertFalse((Set::of(1))->empty());
    }

    public function testFind()
    {
        $sequence = Set::of(1, 2, 3);

        $this->assertSame(
            1,
            $sequence->find(static fn($i) => $i === 1)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(
            2,
            $sequence->find(static fn($i) => $i === 2)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(
            3,
            $sequence->find(static fn($i) => $i === 3)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );

        $this->assertNull(
            $sequence->find(static fn($i) => $i === 0)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
    }

    public function get($map, $index)
    {
        return $map->get($index)->match(
            static fn($value) => $value,
            static fn() => null,
        );
    }
}
