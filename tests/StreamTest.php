<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Stream,
    StreamInterface,
    SizeableInterface,
    PrimitiveInterface,
    Str,
    MapInterface
};

class StreamTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $stream = new Stream('int');

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertInstanceOf(SizeableInterface::class, $stream);
        $this->assertInstanceOf(PrimitiveInterface::class, $stream);
        $this->assertInstanceOf(\Countable::class, $stream);
        $this->assertInstanceOf(\Iterator::class, $stream);
        $this->assertInstanceOf(\ArrayAccess::class, $stream);
        $this->assertSame([], $stream->toPrimitive());
    }

    public function testType()
    {
        $type = (new Stream('int'))->type();

        $this->assertInstanceOf(Str::class, $type);
        $this->assertSame('int', (string) $type);
    }

    public function testSize()
    {
        $this->assertSame(
            2,
            (new Stream('int'))
                ->add(1)
                ->add(2)
                ->size()
        );
    }

    public function testCount()
    {
        $this->assertCount(
            2,
            (new Stream('int'))
                ->add(1)
                ->add(2)
        );
    }

    public function testIterator()
    {
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(1, $stream->current());
        $this->assertSame(0, $stream->key());
        $this->assertTrue($stream->valid());
        $this->assertNull($stream->next());
        $this->assertSame(2, $stream->current());
        $this->assertSame(1, $stream->key());
        $stream->next();
        $stream->next();
        $stream->next();
        $this->assertFalse($stream->valid());
        $this->assertNull($stream->rewind());
        $this->assertTrue($stream->valid());
        $this->assertSame(0, $stream->key());
    }

    public function testOffsetExists()
    {
        $stream = (new Stream('int'))->add(1);

        $this->assertTrue(isset($stream[0]));
        $this->assertFalse(isset($stream[1]));
    }

    public function testOffsetGet()
    {
        $stream = (new Stream('int'))->add(1);

        $this->assertSame(1, $stream[0]);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\OutOfBoundException
     */
    public function testThrowWhenAccessingUnknownIndex()
    {
        (new Stream('int'))[0];
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify a stream
     */
    public function testThrowWhenSettingValue()
    {
        $stream = new Stream('int');
        $stream[0] = 1;
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify a stream
     */
    public function testThrowWhenUnsettingValue()
    {
        $stream = (new Stream('int'))->add(1);
        unset($stream[0]);
    }

    public function testGet()
    {
        $this->assertSame(
            1,
            (new Stream('int'))->add(1)->get(0)
        );
    }

    /**
     * @expectedException Innmind\Immutable\Exception\OutOfBoundException
     */
    public function testThrowWhenGettingUnknownIndex()
    {
        (new Stream('int'))->get(0);
    }

    public function testDiff()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3);
        $b = (new Stream('int'))
            ->add(3)
            ->add(4)
            ->add(5);
        $c = $a->diff($b);

        $this->assertInstanceOf(Stream::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame('int', (string) $c->type());
        $this->assertSame([1, 2, 3], $a->toPrimitive());
        $this->assertSame([3, 4, 5], $b->toPrimitive());
        $this->assertSame([1, 2], $c->toPrimitive());
    }

    public function testDistinct()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(1)
            ->add(1);
        $b = $a->distinct();

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 1, 1], $a->toPrimitive());
        $this->assertSame([1], $b->toPrimitive());
    }

    public function testDrop()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->drop(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 3, 5], $a->toPrimitive());
        $this->assertSame([5], $b->toPrimitive());
    }

    public function testDropEnd()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->dropEnd(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 3, 5], $a->toPrimitive());
        $this->assertSame([1], $b->toPrimitive());
    }

    public function testEquals()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(3)
            ->add(5);
        $b = (new Stream('int'))
            ->add(1)
            ->add(5);
        $c = (new Stream('int'))
            ->add(1)
            ->add(3)
            ->add(5);

        $this->assertTrue($a->equals($c));
        $this->assertTrue($a->equals($a));
        $this->assertFalse($a->equals($b));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage The 2 streams does not reference the same type
     */
    public function testThrowWhenTryingToTestEqualityForDifferentTypes()
    {
        (new Stream('int'))->equals(new Stream('stdClass'));
    }

    public function testFilter()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->filter(function(int $value): bool {
            return $value % 2 === 0;
        });

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toPrimitive());
        $this->assertSame([2, 4], $b->toPrimitive());
    }

    public function testForeach()
    {
        $sum = 0;
        $stream = (new Stream('int'))
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
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $map = $stream->groupBy(function(int $value): int {
            return $value % 3;
        });

        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('integer', (string) $map->keyType());
        $this->assertSame(StreamInterface::class, (string) $map->valueType());
        $this->assertCount(3, $map);
        $this->assertSame('int', (string) $map->get(0)->type());
        $this->assertSame('int', (string) $map->get(1)->type());
        $this->assertSame('int', (string) $map->get(2)->type());
        $this->assertSame([3], $map->get(0)->toPrimitive());
        $this->assertSame([1, 4], $map->get(1)->toPrimitive());
        $this->assertSame([2], $map->get(2)->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\GroupEmptySequenceException
     */
    public function testThrowWhenGroupingEmptyStream()
    {
        (new Stream('int'))->groupBy(function() {});
    }

    public function testFirst()
    {
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(1, $stream->first());
    }

    public function testLast()
    {
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(4, $stream->last());
    }

    public function testContains()
    {
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertTrue($stream->contains(2));
        $this->assertFalse($stream->contains(5));
    }

    public function testIndexOf()
    {
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(0, $stream->indexOf(1));
        $this->assertSame(3, $stream->indexOf(4));
    }

    public function testIndices()
    {
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $indices = $stream->indices();

        $this->assertInstanceOf(StreamInterface::class, $indices);
        $this->assertSame('int', (string) $indices->type());
        $this->assertSame([0, 1, 2, 3], $indices->toPrimitive());
    }

    public function testMap()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->map(function(int $value): int {
            return $value**2;
        });

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toPrimitive());
        $this->assertSame([1, 4, 9, 16], $b->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenTryingToModifyValueTypeInMap()
    {
        (new Stream('int'))
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
        $a = (new Stream('int'))
            ->add(1)
            ->add(2);
        $b = $a->pad(4, 0);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 2], $a->toPrimitive());
        $this->assertSame([1, 2, 0, 0], $b->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenPaddingWithDifferentType()
    {
        (new Stream('int'))->pad(2, '0');
    }

    public function testPartition()
    {
        $map = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->partition(function(int $value): bool {
                return $value % 2 === 0;
            });

        $this->assertInstanceOf(MapInterface::class, $map);
        $this->assertSame('bool', (string) $map->keyType());
        $this->assertSame(StreamInterface::class, (string) $map->valueType());
        $this->assertSame('int', (string) $map->get(true)->type());
        $this->assertSame('int', (string) $map->get(false)->type());
        $this->assertSame([2, 4], $map->get(true)->toPrimitive());
        $this->assertSame([1, 3], $map->get(false)->toPrimitive());
    }

    public function testSlice()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->slice(1, 3);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toPrimitive());
        $this->assertSame([2, 3], $b->toPrimitive());
    }

    public function testSplitAt()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->splitAt(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame(StreamInterface::class, (string) $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toPrimitive());
        $this->assertSame('int', (string) $b->first()->type());
        $this->assertSame('int', (string) $b->last()->type());
        $this->assertSame([1, 2], $b->first()->toPrimitive());
        $this->assertSame([3, 4], $b->last()->toPrimitive());
    }

    public function testTake()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->take(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toPrimitive());
        $this->assertSame([1, 2], $b->toPrimitive());
    }

    public function testTakeEnd()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->takeEnd(2);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 2, 3, 4], $a->toPrimitive());
        $this->assertSame([3, 4], $b->toPrimitive());
    }

    public function testAppend()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2);
        $b = (new Stream('int'))
            ->add(3)
            ->add(4);
        $c = $b->append($a);

        $this->assertInstanceOf(Stream::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame('int', (string) $c->type());
        $this->assertSame([1, 2], $a->toPrimitive());
        $this->assertSame([3, 4], $b->toPrimitive());
        $this->assertSame([3, 4, 1, 2], $c->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage The 2 streams does not reference the same type
     */
    public function testThrowWhenAppendingDifferentTypes()
    {
        (new Stream('int'))->append(new Stream('stdClass'));
    }

    public function testIntersect()
    {
        $a = (new Stream('int'))
            ->add(1)
            ->add(2);
        $b = (new Stream('int'))
            ->add(2)
            ->add(3);
        $c = $b->intersect($a);

        $this->assertInstanceOf(Stream::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame('int', (string) $c->type());
        $this->assertSame([1, 2], $a->toPrimitive());
        $this->assertSame([2, 3], $b->toPrimitive());
        $this->assertSame([2], $c->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage The 2 streams does not reference the same type
     */
    public function testThrowWhenIntersectingDifferentTypes()
    {
        (new Stream('int'))->intersect(new Stream('stdClass'));
    }

    public function testJoin()
    {
        $str = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->join(', ');

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame('1, 2', (string) $str);
    }

    public function testAdd()
    {
        $a = new Stream('int');
        $b = $a->add(1);

        $this->assertInstanceOf(Stream::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([], $a->toPrimitive());
        $this->assertSame([1], $b->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenAddingInvalidType()
    {
        (new Stream('int'))->add(4.2);
    }

    public function testSort()
    {
        $a = (new Stream('int'))
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
        $this->assertSame('int', (string) $a->type());
        $this->assertSame('int', (string) $b->type());
        $this->assertSame([1, 2, 3, 3, 4], $a->toPrimitive());
        $this->assertSame([4, 3, 3, 2, 1], $b->toPrimitive());
    }

    public function testReduce()
    {
        $value = (new Stream('int'))
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
        $stream = (new Stream('int'))
            ->add(1)
            ->add(2)
            ->add(3);
        $stream2 = $stream->clear();

        $this->assertNotSame($stream, $stream2);
        $this->assertSame('int', (string) $stream2->type());
        $this->assertSame([1, 2, 3], $stream->toPrimitive());
        $this->assertSame([], $stream2->toPrimitive());
    }
}
