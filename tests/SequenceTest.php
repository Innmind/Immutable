<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Sequence,
    Str,
    Map,
    Stream,
    Exception\LogicException,
    Exception\OutOfBoundException,
    Exception\GroupEmptySequenceException,
    Exception\ElementNotFoundException
};
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $s = Sequence::of(1);

        $this->assertInstanceOf(\Countable::class, $s);
        $this->assertSame([1], $s->toArray());
    }

    public function testOf()
    {
        $this->assertTrue(Sequence::of(1, 2, 3)->equals(Sequence::of(1, 2, 3)));
    }

    public function testSize()
    {
        $this->assertSame(0, Sequence::of()->size());
        $this->assertSame(0, Sequence::of()->count());
        $this->assertSame(2, Sequence::of('foo', 42)->size());
        $this->assertSame(2, Sequence::of('foo', 42)->count());
    }

    public function testGet()
    {
        $this->assertSame(3, Sequence::of(1, 2, 3)->get(2));
    }

    public function testThrowWhenGettingUnknownIndex()
    {
        $this->expectException(OutOfBoundException::class);

        Sequence::of()->get(0);
    }

    public function testHas()
    {
        $this->assertFalse(Sequence::of()->has(0));
        $this->assertTrue(Sequence::of(1)->has(0));
    }

    public function testDiff()
    {
        $s = Sequence::of(1, 2, 3, 4);
        $s2 = Sequence::of(1, 3);

        $s3 = $s->diff($s2);
        $this->assertNotSame($s, $s3);
        $this->assertNotSame($s2, $s3);
        $this->assertInstanceOf(Sequence::class, $s3);
        $this->assertSame([2, 4], $s3->toArray());
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertSame([1, 3], $s2->toArray());
    }

    public function testDiffObjects()
    {
        $foo = new \stdClass;
        $bar = new \stdClass;
        $baz = new \stdClass;

        $s = Sequence::of($foo, $bar, $baz, $bar);
        $s2 = Sequence::of($bar);

        $s3 = $s->diff($s2);
        $this->assertNotSame($s, $s3);
        $this->assertNotSame($s2, $s3);
        $this->assertInstanceOf(Sequence::class, $s3);
        $this->assertSame([$foo, $baz], $s3->toArray());
        $this->assertSame([$foo, $bar, $baz, $bar], $s->toArray());
        $this->assertSame([$bar], $s2->toArray());
    }

    public function testDistinct()
    {
        $s = Sequence::of(1, 2, 2, 3);

        $s2 = $s->distinct();
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1, 2, 3], $s2->toArray());
        $this->assertSame([1, 2, 2, 3], $s->toArray());
    }

    public function testDistinctObjects()
    {
        $foo = new \stdClass;
        $bar = new \stdClass;
        $baz = new \stdClass;

        $s = Sequence::of($foo, $bar, $foo, $baz);

        $s2 = $s->distinct();
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([$foo, $bar, $baz], $s2->toArray());
        $this->assertSame([$foo, $bar, $foo, $baz], $s->toArray());
    }

    public function testDrop()
    {
        $s = Sequence::of(1, 2, 3);

        $s2 = $s->drop(2);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([3], $s2->toArray());
        $this->assertSame([1, 2, 3], $s->toArray());
    }

    public function testDropEnd()
    {
        $s = Sequence::of(1, 2, 3, 4);

        $s2 = $s->dropEnd(2);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1, 2], $s2->toArray());
        $this->assertSame([1, 2, 3, 4], $s->toArray());
    }

    public function testEquals()
    {
        $s = Sequence::of(1, 2, 3, 4);

        $this->assertTrue($s->equals(Sequence::of(1, 2, 3, 4)));
        $this->assertFalse($s->equals(Sequence::of(1, 2, 3)));
    }

    public function testFilter()
    {
        $s = Sequence::of(1, 2, 3, 4);

        $s2 = $s->filter(function ($v) {
            return $v % 2 === 0;
        });

        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([2, 4], $s2->toArray());
        $this->assertSame([1, 2, 3, 4], $s->toArray());
    }

    public function testForeach()
    {
        $s = Sequence::of(1, 2, 3, 4);
        $count = 0;

        $s->foreach(function ($v) use (&$count) {
            ++$count;
            $this->assertSame($v, $count);
        });
        $this->assertSame(4, $count);
    }

    public function testGroupBy()
    {
        $s = Sequence::of(1, 2, 3, 4);

        $m = $s->groupBy(function(int $v) {
            return $v % 2;
        });
        $this->assertInstanceOf(Map::class, $m);
        $this->assertSame('int', (string) $m->keyType());
        $this->assertSame(Sequence::class, (string) $m->valueType());
        $this->assertSame(2, $m->size());
        $this->assertSame([1, 0], $m->keys()->toArray());
        $this->assertSame([1, 3], $m->get(1)->toArray());
        $this->assertSame([2, 4], $m->get(0)->toArray());
    }

    public function testThrowWhenGroupingAnEmptySequence()
    {
        $this->expectException(GroupEmptySequenceException::class);

        Sequence::of()->groupBy(function() {});
    }

    public function testFirst()
    {
        $s = Sequence::of(1, 2, 3);

        $this->assertSame(1, $s->first());
    }

    public function testThrowWhenAccessingFirstValueOnEmptySequence()
    {
        $this->expectException(OutOfBoundException::class);

        Sequence::of()->first();
    }

    public function testLast()
    {
        $s = Sequence::of(1, 2, 3);

        $this->assertSame(3, $s->last());
    }

    public function testThrowWhenAccessingLastValueOnEmptySequence()
    {
        $this->expectException(OutOfBoundException::class);

        Sequence::of()->last();
    }

    public function testContains()
    {
        $s = Sequence::of(1, 2, 3);

        $this->assertTrue($s->contains(3));
        $this->assertFalse($s->contains('3'));
    }

    public function testIndexOf()
    {
        $s = Sequence::of(1, 2, 3, new \stdClass, $o = new \stdClass);

        $this->assertSame(0, $s->indexOf(1));
        $this->assertSame(1, $s->indexOf(2));
        $this->assertSame(2, $s->indexOf(3));
        $this->assertSame(4, $s->indexOf($o));
    }

    public function testThrowWhenElementNotInSequence()
    {
        $this->expectException(ElementNotFoundException::class);

        Sequence::of()->indexOf(1);
    }

    public function testIndices()
    {
        $indices = Sequence::of(1, 2, 3)->indices();

        $this->assertInstanceOf(Stream::class, $indices);
        $this->assertSame('int', (string) $indices->type());
        $this->assertSame([0, 1, 2], $indices->toArray());
    }

    public function testEmptyIndices()
    {
        $indices = Sequence::of()->indices();

        $this->assertInstanceOf(Stream::class, $indices);
        $this->assertSame('int', (string) $indices->type());
        $this->assertSame([], $indices->toArray());
    }

    public function testMap()
    {
        $s = Sequence::of(1, 2, 3);

        $s2 = $s->map(function ($v) {
            return $v**2;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1, 2, 3], $s->toArray());
        $this->assertSame([1, 4, 9], $s2->toArray());
    }

    public function testPad()
    {
        $s = Sequence::of();

        $s2 = $s->pad(2, null);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([], $s->toArray());
        $this->assertSame([null, null], $s2->toArray());
    }

    public function testPartition()
    {
        $s = Sequence::of(1, 2, 3, 4);

        $s2 = $s->partition(function ($v) {
            return $v % 2 === 0;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Map::class, $s2);
        $this->assertSame('bool', (string) $s2->keyType());
        $this->assertSame(Sequence::class, (string) $s2->valueType());
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertSame(2, $s2->size());
        $this->assertSame([2, 4], $s2->get(true)->toArray());
        $this->assertSame([1, 3], $s2->get(false)->toArray());
    }

    public function testSlice()
    {
        $s = Sequence::of(1, 2, 3, 4, 5, 6, 7);

        $s2 = $s->slice(2, 5);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $s->toArray());
        $this->assertSame([3, 4, 5], $s2->toArray());
    }

    public function testSplitAt()
    {
        $s = Sequence::of(3, 1, 2, 4, 5, 6, 7);

        $s2 = $s->splitAt(3);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Stream::class, $s2);
        $this->assertSame(Sequence::class, (string) $s2->type());
        $this->assertSame([3, 1, 2, 4, 5, 6, 7], $s->toArray());
        $this->assertSame(2, $s2->size());
        $this->assertSame([3, 1, 2], $s2->get(0)->toArray());
        $this->assertSame([4, 5, 6, 7], $s2->get(1)->toArray());
    }

    public function testTake()
    {
        $s = Sequence::of(3, 1, 2, 4, 5, 6, 7);

        $s2 = $s->take(4);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([3, 1, 2, 4, 5, 6, 7], $s->toArray());
        $this->assertSame([3, 1, 2, 4], $s2->toArray());
    }

    public function testTakeEnd()
    {
        $s = Sequence::of(3, 1, 2, 4, 5, 6, 7);

        $s2 = $s->takeEnd(4);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([3, 1, 2, 4, 5, 6, 7], $s->toArray());
        $this->assertSame([4, 5, 6, 7], $s2->toArray());
    }

    public function testAppend()
    {
        $s = Sequence::of(1, 2);

        $s2 = $s->append(Sequence::of(2, 3));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1, 2], $s->toArray());
        $this->assertSame([1, 2, 2, 3], $s2->toArray());
    }

    public function testIntersect()
    {
        $s = Sequence::of(1, 2, 3, 4);

        $s2 = $s->intersect(Sequence::of(2, 3, 5));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertSame([2, 3], $s2->toArray());
    }

    public function testIntersectObjects()
    {
        $foo = new \stdClass;
        $bar = new \stdClass;
        $baz = new \stdClass;

        $s = Sequence::of($foo, $bar, $baz);

        $s2 = $s->intersect(Sequence::of($bar, new \stdClass));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([$foo, $bar, $baz], $s->toArray());
        $this->assertSame([$bar], $s2->toArray());
    }

    public function testJoin()
    {
        $s = Sequence::of(1, 2, 3);

        $s2 = $s->join(', ');
        $this->assertInstanceOf(Str::class, $s2);
        $this->assertSame('1, 2, 3', (string) $s2);
    }

    public function testAdd()
    {
        $s = Sequence::of(1);

        $s2 = $s->add(-1);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1], $s->toArray());
        $this->assertSame([1, -1], $s2->toArray());
    }

    public function testSort()
    {
        $s = Sequence::of(4, 3, 2, 1);

        $s2 = $s->sort(function(int $a, int $b) {
            return $a > $b;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([4, 3, 2, 1], $s->toArray());
        $this->assertSame([1, 2, 3, 4], $s2->toArray());
    }

    public function testReduce()
    {
        $s = Sequence::of(4, 3, 2, 1);

        $v = $s->reduce(
            42,
            function (float $carry, int $value): float {
                return $carry / $value;
            }
        );

        $this->assertSame(1.75, $v);
        $this->assertSame([4, 3, 2, 1], $s->toArray());
    }

    public function testReverse()
    {
        $sequence = Sequence::of(1, 3, 4, 2);
        $reverse = $sequence->reverse();

        $this->assertInstanceOf(Sequence::class, $reverse);
        $this->assertNotSame($sequence, $reverse);
        $this->assertSame([1, 3, 4, 2], $sequence->toArray());
        $this->assertSame([2, 4, 3, 1], $reverse->toArray());
    }

    public function testEmpty()
    {
        $this->assertTrue(Sequence::of()->empty());
        $this->assertFalse(Sequence::of(1)->empty());
    }
}
