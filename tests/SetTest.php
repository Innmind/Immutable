<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Set,
    Map,
    SequenceInterface,
    Str,
    Stream,
    Exception\InvalidArgumentException
};
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testInterface()
    {
        $s = Set::of('int');

        $this->assertInstanceOf(\Countable::class, $s);
        $this->assertSame('int', $s->type());
    }

    public function testOf()
    {
        $this->assertTrue(
            Set::of('int', 1, 1, 2, 3)->equals(
                Set::of('int')
                    ->add(1)
                    ->add(2)
                    ->add(3)
            )
        );
    }

    public function testMixed()
    {
        $set = Set::mixed(1, '2', 3, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('mixed', $set->type());
        $this->assertSame([1, '2', 3], $set->toArray());
    }

    public function testInts()
    {
        $set = Set::ints(1, 2, 3, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('int', $set->type());
        $this->assertSame([1, 2, 3], $set->toArray());
    }

    public function testFloats()
    {
        $set = Set::floats(1, 2, 3.2, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('float', $set->type());
        $this->assertSame([1.0, 2.0, 3.2], $set->toArray());
    }

    public function testStrings()
    {
        $set = Set::strings('1', '2', '3', '1');

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('string', $set->type());
        $this->assertSame(['1', '2', '3'], $set->toArray());
    }

    public function testObjects()
    {
        $a = new \stdClass;
        $b = new \stdClass;
        $c = new \stdClass;
        $set = Set::objects($a, $b, $c, $a);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('object', $set->type());
        $this->assertSame([$a, $b, $c], $set->toArray());
    }

    public function testAdd()
    {
        $this->assertSame(0, Set::of('in')->size());

        $s = Set::of('int')->add(42);

        $this->assertSame(1, $s->size());
        $this->assertSame(1, $s->count());
        $s->add(24);
        $this->assertSame(1, $s->size());
        $s = $s->add(24);
        $this->assertInstanceOf(Set::class, $s);
        $this->assertSame(2, $s->size());
        $s = $s->add(24);
        $this->assertSame(2, $s->size());
        $this->assertSame([42, 24], $s->toArray());

        $this->assertSame(
            [1, 2, 3],
            Set::ints(1)(2)(3)->toArray(),
        );
    }

    public function testThrowWhenAddindInvalidElementType()
    {
        $this->expectException(InvalidArgumentException::class);

        Set::of('int')->add(42.0);
    }

    public function testIntersect()
    {
        $s = Set::of('int')
            ->add(24)
            ->add(42)
            ->add(66);

        $s2 = $s->intersect(Set::of('int')->add(42));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([24, 42, 66], $s->toArray());
        $this->assertSame([42], $s2->toArray());
    }

    public function testThrowWhenIntersectingSetsOfDifferentTypes()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        Set::of('int')->intersect(Set::of('float'));
    }

    public function testContains()
    {
        $s = Set::of('int');

        $this->assertFalse($s->contains(42));
        $s = $s->add(42);
        $this->assertTrue($s->contains(42));
    }

    public function testRemove()
    {
        $s = Set::of('int')
            ->add(24)
            ->add(42)
            ->add(66)
            ->add(90)
            ->add(114);

        $s2 = $s->remove(42);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([24, 42, 66, 90, 114], $s->toArray());
        $this->assertSame([24, 66, 90, 114], $s2->toArray());
        $this->assertSame([42, 66, 90, 114], $s->remove(24)->toArray());
        $this->assertSame([24, 42, 90, 114], $s->remove(66)->toArray());
        $this->assertSame([24, 42, 66, 114], $s->remove(90)->toArray());
        $this->assertSame([24, 42, 66, 90], $s->remove(114)->toArray());
    }

    public function testDiff()
    {
        $s = Set::of('int')
            ->add(24)
            ->add(42)
            ->add(66);

        $s2 = $s->diff(Set::of('int')->add(42));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame($s->type(), $s2->type());
        $this->assertSame([24, 42, 66], $s->toArray());
        $this->assertSame([24, 66], $s2->toArray());
    }

    public function testThrowWhenDiffingSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        Set::of('int')->diff(Set::of('float'));
    }

    public function testEquals()
    {
        $s = Set::of('int')
            ->add(24)
            ->add(42)
            ->add(66);

        $this->assertTrue(
            $s->equals(
                Set::of('int')
                    ->add(24)
                    ->add(66)
                    ->add(42)
            )
        );
        $this->assertTrue(Set::of('int')->equals(Set::of('int')));
        $this->assertFalse(
            $s->equals(
                Set::of('int')
                    ->add(24)
                    ->add(66)
            )
        );
    }

    public function testThrowWhenCheckingEqualityBetweenSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        Set::of('int')->equals(Set::of('float'));
    }

    public function testFilter()
    {
        $s = Set::of('int')
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
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertSame([2, 4], $s2->toArray());
    }

    public function testForeach()
    {
        $s = Set::of('int')
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
        $s = Set::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $m = $s->groupBy(function(int $v) {
            return $v % 2;
        });
        $this->assertInstanceOf(Map::class, $m);
        $this->assertSame('int', $m->keyType());
        $this->assertSame(Set::class, $m->valueType());
        $this->assertSame('int', $m->get(0)->type());
        $this->assertSame('int', $m->get(1)->type());
        $this->assertSame([1, 0], $m->keys()->toArray());
        $this->assertSame([1, 3], $m->get(1)->toArray());
        $this->assertSame([2, 4], $m->get(0)->toArray());
    }

    public function testMap()
    {
        $s = Set::of('int')
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
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertSame([1, 4, 9, 16], $s2->toArray());
    }

    public function testThrowWhenTryingToModifyValueTypeInMap()
    {
        $this->expectException(InvalidArgumentException::class);

        Set::of('int')
            ->add(1)
            ->map(function(int $value) {
                return (string) $value;
            });
    }

    public function testPartition()
    {
        $s = Set::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->partition(function(int $v) {
            return $v % 2 === 0;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Map::class, $s2);
        $this->assertSame('bool', $s2->keyType());
        $this->assertSame(Set::class, $s2->valueType());
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertInstanceOf(Set::class, $s2->get(true));
        $this->assertInstanceOf(Set::class, $s2->get(false));
        $this->assertSame($s->type(), $s2->get(true)->type());
        $this->assertSame($s->type(), $s2->get(false)->type());
        $this->assertSame([2, 4], $s2->get(true)->toArray());
        $this->assertSame([1, 3], $s2->get(false)->toArray());
    }

    public function testJoin()
    {
        $s = Set::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->join(', ');
        $this->assertInstanceOf(Str::class, $s2);
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertSame('1, 2, 3, 4', (string) $s2);
    }

    public function testSort()
    {
        $s = Set::of('int')
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->sort(function(int $a, int $b) {
            return $a < $b;
        });
        $this->assertInstanceOf(Stream::class, $s2);
        $this->assertSame('int', $s2->type());
        $this->assertSame([1, 2, 3, 4], $s->toArray());
        $this->assertSame([4, 3, 2, 1], $s2->toArray());
    }

    public function testMerge()
    {
        $s = Set::of('int')
            ->add(24)
            ->add(42)
            ->add(66);

        $this->assertTrue(
            $s
                ->merge(
                    Set::of('int')
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
                    Set::of('int')
                        ->add(90)
                        ->add(114)
                )
                ->toArray()
        );
        $this->assertSame([24, 42, 66], $s->toArray());
        $this->assertSame($s->type(), $s->merge(Set::of('int'))->type());
    }

    public function testThrowWhenMergingSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 sets does not reference the same type');

        Set::of('int')->merge(Set::of('float'));
    }

    public function testReduce()
    {
        $s = Set::of('int')
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
        $this->assertSame([4, 3, 2], $s->toArray());
    }

    public function testVariableSet()
    {
        $this->assertSame(
            ['foo', 42, 42.1, true, []],
            Set::of('variable')
                ->add('foo')
                ->add(42)
                ->add(42.1)
                ->add(true)
                ->add([])
                ->toArray()
        );
    }

    public function testEmpty()
    {
        $this->assertTrue(Set::of('int')->empty());
        $this->assertFalse(Set::of('int', 1)->empty());
    }
}
