<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Set,
    SetInterface,
    SizeableInterface,
    PrimitiveInterface,
    MapInterface,
    SequenceInterface,
    Str,
    StreamInterface,
    Exception\InvalidArgumentException
};
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testInterface()
    {
        $s = new Set('int');

        $this->assertInstanceOf(SetInterface::class, $s);
        $this->assertInstanceOf(SizeableInterface::class, $s);
        $this->assertInstanceOf(PrimitiveInterface::class, $s);
        $this->assertInstanceOf(\Countable::class, $s);
        $this->assertInstanceOf(\Iterator::class, $s);
        $this->assertInstanceOf(Str::class, $s->type());
        $this->assertSame('int', (string) $s->type());
    }

    public function testOf()
    {
        $this->assertTrue(
            Set::of('int', 1, 1, 2, 3)->equals(
                (new Set('int'))
                    ->add(1)
                    ->add(2)
                    ->add(3)
            )
        );
    }

    public function testAdd()
    {
        $this->assertSame(0, (new Set('in'))->size());

        $s = (new Set('int'))->add(42);

        $this->assertSame(1, $s->size());
        $this->assertSame(1, $s->count());
        $s->add(24);
        $this->assertSame(1, $s->size());
        $s = $s->add(24);
        $this->assertInstanceOf(Set::class, $s);
        $this->assertSame(2, $s->size());
        $s = $s->add(24);
        $this->assertSame(2, $s->size());
        $this->assertSame([42, 24], $s->toPrimitive());
    }

    public function testThrowWhenAddindInvalidElementType()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Set('int'))->add(42.0);
    }

    public function testIterator()
    {
        $s = (new Set('int'))
            ->add(24)
            ->add(42)
            ->add(66);

        $this->assertSame(24, $s->current());
        $this->assertSame(0, $s->key());
        $this->assertTrue($s->valid());
        $this->assertSame(null, $s->next());
        $this->assertSame(42, $s->current());
        $this->assertSame(1, $s->key());
        $this->assertTrue($s->valid());
        $s->next();
        $s->next();
        $this->assertFalse($s->valid());
        $this->assertSame(null, $s->rewind());
        $this->assertSame(24, $s->current());
        $this->assertTrue($s->valid());
    }

    public function testIntersect()
    {
        $s = (new Set('int'))
            ->add(24)
            ->add(42)
            ->add(66);

        $s2 = $s->intersect((new Set('int'))->add(42));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([24, 42, 66], $s->toPrimitive());
        $this->assertSame([42], $s2->toPrimitive());
    }

    public function testThrowWhenIntersectingSetsOfDifferentTypes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        (new Set('int'))->intersect(new Set('float'));
    }

    public function testContains()
    {
        $s = new Set('int');

        $this->assertFalse($s->contains(42));
        $s = $s->add(42);
        $this->assertTrue($s->contains(42));
    }

    public function testRemove()
    {
        $s = (new Set('int'))
            ->add(24)
            ->add(42)
            ->add(66)
            ->add(90)
            ->add(114);

        $s2 = $s->remove(42);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([24, 42, 66, 90, 114], $s->toPrimitive());
        $this->assertSame([24, 66, 90, 114], $s2->toPrimitive());
        $this->assertSame([42, 66, 90, 114], $s->remove(24)->toPrimitive());
        $this->assertSame([24, 42, 90, 114], $s->remove(66)->toPrimitive());
        $this->assertSame([24, 42, 66, 114], $s->remove(90)->toPrimitive());
        $this->assertSame([24, 42, 66, 90], $s->remove(114)->toPrimitive());
    }

    public function testDiff()
    {
        $s = (new Set('int'))
            ->add(24)
            ->add(42)
            ->add(66);

        $s2 = $s->diff((new Set('int'))->add(42));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([24, 42, 66], $s->toPrimitive());
        $this->assertSame([24, 66], $s2->toPrimitive());
    }

    public function testThrowWhenDiffingSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        (new Set('int'))->diff(new Set('float'));
    }

    public function testEquals()
    {
        $s = (new Set('int'))
            ->add(24)
            ->add(42)
            ->add(66);

        $this->assertTrue(
            $s->equals(
                (new Set('int'))
                    ->add(24)
                    ->add(66)
                    ->add(42)
            )
        );
        $this->assertTrue(Set::of('int')->equals(new Set('int')));
        $this->assertFalse(
            $s->equals(
                (new Set('int'))
                    ->add(24)
                    ->add(66)
            )
        );
    }

    public function testThrowWhenCheckingEqualityBetweenSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        (new Set('int'))->equals(new Set('float'));
    }

    public function testFilter()
    {
        $s = (new Set('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->filter(function(int $v) {
            return $v % 2 === 0;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([1, 2, 3, 4], $s->toPrimitive());
        $this->assertSame([2, 4], $s2->toPrimitive());
    }

    public function testForeach()
    {
        $s = (new Set('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $count = 0;

        $s->foreach(function(int $v) use (&$count) {
            $this->assertSame(++$count, $v);
        });
        $this->assertSame(4, $count);
    }

    public function testGroupBy()
    {
        $s = (new Set('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $m = $s->groupBy(function(int $v) {
            return $v % 2;
        });
        $this->assertInstanceOf(MapInterface::class, $m);
        $this->assertSame('int', (string) $m->keyType());
        $this->assertSame(SetInterface::class, (string) $m->valueType());
        $this->assertSame('int', (string) $m->get(0)->type());
        $this->assertSame('int', (string) $m->get(1)->type());
        $this->assertSame([1, 0], $m->keys()->toPrimitive());
        $this->assertSame([1, 3], $m->get(1)->toPrimitive());
        $this->assertSame([2, 4], $m->get(0)->toPrimitive());
    }

    public function testMap()
    {
        $s = (new Set('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->map(function(int $v) {
            return $v**2;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([1, 2, 3, 4], $s->toPrimitive());
        $this->assertSame([1, 4, 9, 16], $s2->toPrimitive());
    }

    public function testThrowWhenTryingToModifyValueTypeInMap()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Set('int'))
            ->add(1)
            ->map(function(int $value) {
                return (string) $value;
            });
    }

    public function testPartition()
    {
        $s = (new Set('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->partition(function(int $v) {
            return $v % 2 === 0;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(MapInterface::class, $s2);
        $this->assertSame('bool', (string) $s2->keyType());
        $this->assertSame(SetInterface::class, (string) $s2->valueType());
        $this->assertSame([1, 2, 3, 4], $s->toPrimitive());
        $this->assertInstanceOf(Set::class, $s2->get(true));
        $this->assertInstanceOf(Set::class, $s2->get(false));
        $this->assertSame($s->type(), $s2->get(true)->type());
        $this->assertSame($s->type(), $s2->get(false)->type());
        $this->assertSame([2, 4], $s2->get(true)->toPrimitive());
        $this->assertSame([1, 3], $s2->get(false)->toPrimitive());
    }

    public function testJoin()
    {
        $s = (new Set('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->join(', ');
        $this->assertInstanceOf(Str::class, $s2);
        $this->assertSame([1, 2, 3, 4], $s->toPrimitive());
        $this->assertSame('1, 2, 3, 4', (string) $s2);
    }

    public function testSort()
    {
        $s = (new Set('int'))
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->sort(function(int $a, int $b) {
            return $a < $b;
        });
        $this->assertInstanceOf(StreamInterface::class, $s2);
        $this->assertSame('int', (string) $s2->type());
        $this->assertSame([1, 2, 3, 4], $s->toPrimitive());
        $this->assertSame([4, 3, 2, 1], $s2->toPrimitive());
    }

    public function testMerge()
    {
        $s = (new Set('int'))
            ->add(24)
            ->add(42)
            ->add(66);

        $this->assertTrue(
            $s
                ->merge(
                    (new Set('int'))
                        ->add(24)
                        ->add(42)
                        ->add(66)
                )
                ->equals($s)
        );
        $this->assertSame(
            [24, 42, 66, 90, 114],
            $s
                ->merge(
                    (new Set('int'))
                        ->add(90)
                        ->add(114)
                )
                ->toPrimitive()
        );
        $this->assertSame([24, 42, 66], $s->toPrimitive());
        $this->assertSame($s->type(), $s->merge(new Set('int'))->type());
    }

    public function testThrowWhenMergingSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        (new Set('int'))->merge(new Set('float'));
    }

    public function testReduce()
    {
        $s = (new Set('int'))
            ->add(4)
            ->add(3)
            ->add(2);

        $v = $s->reduce(
            42,
            function (float $carry, int $value): float {
                return $carry / $value;
            }
        );

        $this->assertSame(1.75, $v);
        $this->assertSame([4, 3, 2], $s->toPrimitive());
    }

    public function testVariableSet()
    {
        $this->assertSame(
            ['foo', 42, 42.1, true, []],
            (new Set('variable'))
                ->add('foo')
                ->add(42)
                ->add(42.1)
                ->add(true)
                ->add([])
                ->toPrimitive()
        );
    }
}
