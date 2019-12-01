<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Set;

use Innmind\Immutable\{
    Set\Primitive,
    Set\Implementation,
    Set,
    Map,
    Str,
    Sequence,
};
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Implementation::class,
            new Primitive('int'),
        );
    }

    public function testIsOfType()
    {
        $this->assertTrue((new Primitive('int'))->isOfType('int'));
        $this->assertFalse((new Primitive('int'))->isOfType('string'));
    }

    public function testType()
    {
        $this->assertSame('int', (new Primitive('int'))->type());
    }

    public function testSize()
    {
        $this->assertSame(2, (new Primitive('int', 1, 2))->size());
        $this->assertSame(2, (new Primitive('int', 1, 2))->count());
    }

    public function testToArray()
    {
        $this->assertSame([1, 2], (new Primitive('int', 1, 2))->toArray());
    }

    public function testIntersect()
    {
        $a = new Primitive('int', 1, 2);
        $b = new Primitive('int', 2, 3);
        $c = $a->intersect($b);

        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([2, 3], $b->toArray());
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([2], $c->toArray());
    }

    public function testAdd()
    {
        $a = new Primitive('int', 1);
        $b = $a->add(2);

        $this->assertSame([1], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2], $b->toArray());
        $this->assertSame($b, $b->add(2));
    }

    public function testContains()
    {
        $set = new Primitive('int', 1);

        $this->assertTrue($set->contains(1));
        $this->assertFalse($set->contains(2));
    }

    public function testRemove()
    {
        $a = new Primitive('int', 1, 2, 3, 4);
        $b = $a->remove(3);

        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([1, 2, 4], $b->toArray());
        $this->assertSame($a, $a->remove(5));
    }

    public function testDiff()
    {
        $a = new Primitive('int', 1, 2, 3);
        $b = new Primitive('int', 2, 4);
        $c = $a->diff($b);

        $this->assertSame([1, 2, 3], $a->toArray());
        $this->assertSame([2, 4], $b->toArray());
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 3], $c->toArray());
    }

    public function testEquals()
    {
        $this->assertTrue((new Primitive('int', 1, 2))->equals(new Primitive('int', 1, 2)));
        $this->assertFalse((new Primitive('int', 1, 2))->equals(new Primitive('int', 1)));
        $this->assertFalse((new Primitive('int', 1, 2))->equals(new Primitive('int', 1, 2, 3)));
    }

    public function testFilter()
    {
        $a = new Primitive('int', 1, 2, 3, 4);
        $b = $a->filter(fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4], $b->toArray());
    }

    public function testForeach()
    {
        $set = new Primitive('int', 1, 2, 3, 4);
        $calls = 0;
        $sum = 0;

        $this->assertNull($set->foreach(function($i) use (&$calls, &$sum) {
            ++$calls;
            $sum += $i;
        }));

        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testGroupBy()
    {
        $set = new Primitive('int', 1, 2, 3, 4);
        $groups = $set->groupBy(fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], $set->toArray());
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertTrue($groups->isOfType('int', Set::class));
        $this->assertCount(2, $groups);
        $this->assertTrue($groups->get(0)->isOfType('int'));
        $this->assertTrue($groups->get(1)->isOfType('int'));
        $this->assertSame([2, 4], $groups->get(0)->toArray());
        $this->assertSame([1, 3], $groups->get(1)->toArray());
    }

    public function testMap()
    {
        $a = new Primitive('int', 1, 2, 3);
        $b = $a->map(fn($i) => $i * 2);

        $this->assertSame([1, 2, 3], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([2, 4, 6], $b->toArray());
    }

    public function testThrowWhenTryingToModifyTheTypeWhenMapping()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

        (new Primitive('int', 1))->map(fn($i) => (string) $i);
    }

    public function testPartition()
    {
        $set = new Primitive('int', 1, 2, 3, 4);
        $groups = $set->partition(fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], $set->toArray());
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertTrue($groups->isOfType('bool', Set::class));
        $this->assertCount(2, $groups);
        $this->assertTrue($groups->get(true)->isOfType('int'));
        $this->assertTrue($groups->get(false)->isOfType('int'));
        $this->assertSame([2, 4], $groups->get(true)->toArray());
        $this->assertSame([1, 3], $groups->get(false)->toArray());
    }

    public function testJoin()
    {
        $set = new Primitive('int', 1, 2, 3, 4);
        $str = $set->join('|');

        $this->assertSame([1, 2, 3, 4], $set->toArray());
        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1|2|3|4', (string) $str);
    }

    public function testSort()
    {
        $set = new Primitive('int', 1, 4, 3, 2);
        $sorted = $set->sort(fn($a, $b) => $a > $b);

        $this->assertSame([1, 4, 3, 2], $set->toArray());
        $this->assertInstanceOf(Sequence::class, $sorted);
        $this->assertSame([1, 2, 3, 4], $sorted->toArray());
    }

    public function testMerge()
    {
        $a = new Primitive('int', 1, 2);
        $b = new Primitive('int', 2, 3);
        $c = $a->merge($b);

        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([2, 3], $b->toArray());
        $this->assertInstanceOf(Primitive::class, $c);
        $this->assertSame([1, 2, 3], $c->toArray());
    }

    public function testReduce()
    {
        $set = new Primitive('int', 1, 2, 3, 4);

        $this->assertSame(10, $set->reduce(0, fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $a = new Primitive('int', 1);
        $b = $a->clear();

        $this->assertSame([1], $a->toArray());
        $this->assertInstanceOf(Primitive::class, $b);
        $this->assertSame([], $b->toArray());
    }

    public function testEmpty()
    {
        $this->assertTrue((new Primitive('int'))->empty());
        $this->assertFalse((new Primitive('int', 1))->empty());
    }
}
