<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Stream,
    Str,
    Map,
    Exception\OutOfBoundException,
    Exception\LogicException,
    Exception\GroupEmptySequenceException
};
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testInterface()
    {
        $stream = Stream::of('int');

        $this->assertInstanceOf(\Countable::class, $stream);
        $this->assertSame([], $stream->toArray());
    }

    public function testOf()
    {
        $this->assertTrue(
            Stream::of('int', 1, 2, 3)->equals(
                Stream::of('int')
                    ->add(1)
                    ->add(2)
                    ->add(3)
            )
        );
    }

    public function testMixed()
    {
        $stream = Stream::mixed(1, '2', 3);

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('mixed', $stream->type());
        $this->assertSame([1, '2', 3], $stream->toArray());
    }

    public function testInts()
    {
        $stream = Stream::ints(1, 2, 3);

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('int', $stream->type());
        $this->assertSame([1, 2, 3], $stream->toArray());
    }

    public function testFloats()
    {
        $stream = Stream::floats(1, 2, 3.2);

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('float', $stream->type());
        $this->assertSame([1.0, 2.0, 3.2], $stream->toArray());
    }

    public function testStrings()
    {
        $stream = Stream::strings('1', '2', '3');

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('string', $stream->type());
        $this->assertSame(['1', '2', '3'], $stream->toArray());
    }

    public function testObjects()
    {
        $a = new \stdClass;
        $b = new \stdClass;
        $c = new \stdClass;
        $stream = Stream::objects($a, $b, $c);

        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertSame('object', $stream->type());
        $this->assertSame([$a, $b, $c], $stream->toArray());
    }

    public function testType()
    {
        $type = Stream::of('int')->type();

        $this->assertSame('int', $type);
    }

    public function testSize()
    {
        $this->assertSame(
            2,
            Stream::of('int')
                ->add(1)
                ->add(2)
                ->size()
        );
    }

    public function testCount()
    {
        $this->assertCount(
            2,
            Stream::of('int')
                ->add(1)
                ->add(2)
        );
    }

    public function testGet()
    {
        $this->assertSame(
            1,
            Stream::of('int')->add(1)->get(0)
        );
    }

    public function testThrowWhenGettingUnknownIndex()
    {
        $this->expectException(OutOfBoundException::class);

        Stream::of('int')->get(0);
    }

    public function testDiff()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3);
        $b = Stream::of('int')
            ->add(3)
            ->add(4)
            ->add(5);
        $c = $a->diff($b);

        $this->assertInstanceOf(Stream::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame('int', $c->type());
        $this->assertSame([1, 2, 3], $a->toArray());
        $this->assertSame([3, 4, 5], $b->toArray());
        $this->assertSame([1, 2], $c->toArray());
    }

    public function testDistinct()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(1)
            ->add(1);
        $b = $a->distinct();

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 1, 1], $a->toArray());
        $this->assertSame([1], $b->toArray());
    }

    public function testDrop()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->drop(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 3, 5], $a->toArray());
        $this->assertSame([5], $b->toArray());
    }

    public function testDropEnd()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->dropEnd(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 3, 5], $a->toArray());
        $this->assertSame([1], $b->toArray());
    }

    public function testEquals()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(3)
            ->add(5);
        $b = Stream::of('int')
            ->add(1)
            ->add(5);
        $c = Stream::of('int')
            ->add(1)
            ->add(3)
            ->add(5);

        $this->assertTrue($a->equals($c));
        $this->assertTrue($a->equals($a));
        $this->assertFalse($a->equals($b));
    }

    public function testThrowWhenTryingToTestEqualityForDifferentTypes()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Stream<int>, Stream<stdClass> given');

        Stream::of('int')->equals(Stream::of('stdClass'));
    }

    public function testFilter()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->filter(function(int $value): bool {
            return $value % 2 === 0;
        });

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertSame([2, 4], $b->toArray());
    }

    public function testForeach()
    {
        $sum = 0;
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->foreach(function(int $value) use (&$sum) {
                $sum += $value;
            });

        $this->assertSame(10, $sum);
    }

    public function testGroupBy()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $map = $stream->groupBy(function(int $value): int {
            return $value % 3;
        });

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame('int', $map->keyType());
        $this->assertSame(Stream::class, $map->valueType());
        $this->assertCount(3, $map);
        $this->assertSame('int', $map->get(0)->type());
        $this->assertSame('int', $map->get(1)->type());
        $this->assertSame('int', $map->get(2)->type());
        $this->assertSame([3], $map->get(0)->toArray());
        $this->assertSame([1, 4], $map->get(1)->toArray());
        $this->assertSame([2], $map->get(2)->toArray());
    }

    public function testThrowWhenGroupingEmptyStream()
    {
        $this->expectException(GroupEmptySequenceException::class);

        Stream::of('int')->groupBy(function() {});
    }

    public function testFirst()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(1, $stream->first());
    }

    public function testLast()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(4, $stream->last());
    }

    public function testContains()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertTrue($stream->contains(2));
        $this->assertFalse($stream->contains(5));
    }

    public function testIndexOf()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(0, $stream->indexOf(1));
        $this->assertSame(3, $stream->indexOf(4));
    }

    public function testIndices()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $indices = $stream->indices();

        $this->assertInstanceOf(Stream::class, $indices);
        $this->assertSame('int', $indices->type());
        $this->assertSame([0, 1, 2, 3], $indices->toArray());
    }

    public function testEmptyIndices()
    {
        $stream = Stream::of('int');
        $indices = $stream->indices();

        $this->assertInstanceOf(Stream::class, $indices);
        $this->assertSame('int', $indices->type());
        $this->assertSame([], $indices->toArray());
    }

    public function testMap()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->map(function(int $value): int {
            return $value**2;
        });

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertSame([1, 4, 9, 16], $b->toArray());
    }

    public function testThrowWhenTryingToModifyValueTypeInMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

        Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->map(function(int $value) {
                return (string) $value;
            });
    }

    public function testPad()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2);
        $b = $a->pad(4, 0);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([1, 2, 0, 0], $b->toArray());
    }

    public function testThrowWhenPaddingWithDifferentType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type int, string given');

        Stream::of('int')->pad(2, '0');
    }

    public function testPartition()
    {
        $map = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->partition(function(int $value): bool {
                return $value % 2 === 0;
            });

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame('bool', $map->keyType());
        $this->assertSame(Stream::class, $map->valueType());
        $this->assertSame('int', $map->get(true)->type());
        $this->assertSame('int', $map->get(false)->type());
        $this->assertSame([2, 4], $map->get(true)->toArray());
        $this->assertSame([1, 3], $map->get(false)->toArray());
    }

    public function testSlice()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->slice(1, 3);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertSame([2, 3], $b->toArray());
    }

    public function testSplitAt()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->splitAt(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame(Stream::class, $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertSame('int', $b->first()->type());
        $this->assertSame('int', $b->last()->type());
        $this->assertSame([1, 2], $b->first()->toArray());
        $this->assertSame([3, 4], $b->last()->toArray());
    }

    public function testTake()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->take(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertSame([1, 2], $b->toArray());
    }

    public function testTakeEnd()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->takeEnd(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toArray());
        $this->assertSame([3, 4], $b->toArray());
    }

    public function testAppend()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2);
        $b = Stream::of('int')
            ->add(3)
            ->add(4);
        $c = $b->append($a);

        $this->assertInstanceOf(Stream::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame('int', $c->type());
        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([3, 4], $b->toArray());
        $this->assertSame([3, 4, 1, 2], $c->toArray());
    }

    public function testThrowWhenAppendingDifferentTypes()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Stream<int>, Stream<stdClass> given');

        Stream::of('int')->append(Stream::of('stdClass'));
    }

    public function testIntersect()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2);
        $b = Stream::of('int')
            ->add(2)
            ->add(3);
        $c = $b->intersect($a);

        $this->assertInstanceOf(Stream::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame('int', $c->type());
        $this->assertSame([1, 2], $a->toArray());
        $this->assertSame([2, 3], $b->toArray());
        $this->assertSame([2], $c->toArray());
    }

    public function testThrowWhenIntersectingDifferentTypes()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Stream<int>, Stream<stdClass> given');

        Stream::of('int')->intersect(Stream::of('stdClass'));
    }

    public function testJoin()
    {
        $str = Stream::of('int')
            ->add(1)
            ->add(2)
            ->join(', ');

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1, 2', (string) $str);
    }

    public function testAdd()
    {
        $a = Stream::of('int');
        $b = $a->add(1);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([], $a->toArray());
        $this->assertSame([1], $b->toArray());

        $this->assertSame(
            [1, 2, 3],
            Stream::ints(1)(2)(3)->toArray(),
        );
    }

    public function testThrowWhenAddingInvalidType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, float given');

        Stream::of('int')->add(4.2);
    }

    public function testSort()
    {
        $a = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(3)
            ->add(4);
        $b = $a->sort(function(int $a, int $b): bool {
            return $b > $a;
        });

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', $a->type());
        $this->assertSame('int', $b->type());
        $this->assertSame([1, 2, 3, 3, 4], $a->toArray());
        $this->assertSame([4, 3, 3, 2, 1], $b->toArray());
    }

    public function testReduce()
    {
        $value = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->reduce(
                0,
                function(int $carry, int $value): int {
                    return $carry + $value;
                }
            );

        $this->assertSame(10, $value);
    }

    public function testClear()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(2)
            ->add(3);
        $stream2 = $stream->clear();

        $this->assertNotSame($stream, $stream2);
        $this->assertSame('int', $stream2->type());
        $this->assertSame([1, 2, 3], $stream->toArray());
        $this->assertSame([], $stream2->toArray());
    }

    public function testReverse()
    {
        $stream = Stream::of('int')
            ->add(1)
            ->add(3)
            ->add(4)
            ->add(2);
        $reverse = $stream->reverse();

        $this->assertInstanceOf(Stream::class, $reverse);
        $this->assertNotSame($stream, $reverse);
        $this->assertSame([1, 3, 4, 2], $stream->toArray());
        $this->assertSame([2, 4, 3, 1], $reverse->toArray());
    }

    public function testEmpty()
    {
        $this->assertTrue(Stream::of('int')->empty());
        $this->assertFalse(Stream::of('int', 1)->empty());
    }
}
