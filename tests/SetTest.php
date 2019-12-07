<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Set,
    Map,
    Str,
    Sequence,
};
use function Innmind\Immutable\unwrap;
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

    public function testDefer()
    {
        $loaded = false;
        $set = Set::defer('int', (function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());

        $this->assertInstanceOf(Set::class, $set);
        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], unwrap($set));
        $this->assertTrue($loaded);
    }

    public function testMixed()
    {
        $set = Set::mixed(1, '2', 3, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('mixed', $set->type());
        $this->assertSame([1, '2', 3], unwrap($set));
    }

    public function testInts()
    {
        $set = Set::ints(1, 2, 3, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('int', $set->type());
        $this->assertSame([1, 2, 3], unwrap($set));
    }

    public function testFloats()
    {
        $set = Set::floats(1, 2, 3.2, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('float', $set->type());
        $this->assertSame([1.0, 2.0, 3.2], unwrap($set));
    }

    public function testStrings()
    {
        $set = Set::strings('1', '2', '3', '1');

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('string', $set->type());
        $this->assertSame(['1', '2', '3'], unwrap($set));
    }

    public function testObjects()
    {
        $a = new \stdClass;
        $b = new \stdClass;
        $c = new \stdClass;
        $set = Set::objects($a, $b, $c, $a);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame('object', $set->type());
        $this->assertSame([$a, $b, $c], unwrap($set));
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
        $this->assertSame([42, 24], unwrap($s));

        $this->assertSame(
            [1, 2, 3],
            unwrap(Set::ints(1)(2)(3)),
        );
    }

    public function testThrowWhenAddindInvalidElementType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, float given');

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
        $this->assertSame([24, 42, 66], unwrap($s));
        $this->assertSame([42], unwrap($s2));
    }

    public function testThrowWhenIntersectingSetsOfDifferentTypes()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Set<int>, Set<float> given');

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
        $this->assertSame([24, 42, 66, 90, 114], unwrap($s));
        $this->assertSame([24, 66, 90, 114], unwrap($s2));
        $this->assertSame([42, 66, 90, 114], unwrap($s->remove(24)));
        $this->assertSame([24, 42, 90, 114], unwrap($s->remove(66)));
        $this->assertSame([24, 42, 66, 114], unwrap($s->remove(90)));
        $this->assertSame([24, 42, 66, 90], unwrap($s->remove(114)));
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
        $this->assertSame([24, 42, 66], unwrap($s));
        $this->assertSame([24, 66], unwrap($s2));
    }

    public function testThrowWhenDiffingSetsOfDifferentType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Set<int>, Set<float> given');

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
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Set<int>, Set<float> given');

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
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertSame([2, 4], unwrap($s2));
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
        $this->assertSame([1, 0], unwrap($m->keys()));
        $this->assertSame([1, 3], unwrap($m->get(1)));
        $this->assertSame([2, 4], unwrap($m->get(0)));
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
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertSame([1, 4, 9, 16], unwrap($s2));
    }

    public function testThrowWhenTryingToModifyValueTypeInMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

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
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertInstanceOf(Set::class, $s2->get(true));
        $this->assertInstanceOf(Set::class, $s2->get(false));
        $this->assertSame($s->type(), $s2->get(true)->type());
        $this->assertSame($s->type(), $s2->get(false)->type());
        $this->assertSame([2, 4], unwrap($s2->get(true)));
        $this->assertSame([1, 3], unwrap($s2->get(false)));
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
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame('int', $s2->type());
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertSame([4, 3, 2, 1], unwrap($s2));
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
            unwrap($s->merge(
                Set::of('int')
                    ->add(90)
                    ->add(114)
            )),
        );
        $this->assertSame([24, 42, 66], unwrap($s));
        $this->assertSame($s->type(), $s->merge(Set::of('int'))->type());
    }

    public function testThrowWhenMergingSetsOfDifferentType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Set<int>, Set<float> given');

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
        $this->assertSame([4, 3, 2], unwrap($s));
    }

    public function testVariableSet()
    {
        $this->assertSame(
            ['foo', 42, 42.1, true, []],
            unwrap(Set::of('variable')
                ->add('foo')
                ->add(42)
                ->add(42.1)
                ->add(true)
                ->add([]))
        );
    }

    public function testEmpty()
    {
        $this->assertTrue(Set::of('int')->empty());
        $this->assertFalse(Set::of('int', 1)->empty());
    }

    public function testToSequenceOf()
    {
        $set = Set::ints(1, 2, 3);
        $sequence = $set->toSequenceOf('string|int', function($i) {
            yield (string) $i;
            yield $i;
        });

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(
            ['1', 1, '2', 2, '3', 3],
            unwrap($sequence),
        );
        $this->assertSame(
            [1, 2, 3],
            unwrap($set->toSequenceOf('int')),
        );
    }

    public function testToSetOf()
    {
        $initial = Set::ints(1, 2, 3);
        $set = $initial->toSetOf('string|int', function($i) {
            yield (string) $i;
            yield $i;
        });

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(
            ['1', 1, '2', 2, '3', 3],
            unwrap($set),
        );
        $this->assertSame(
            [1, 2, 3],
            unwrap($initial->toSetOf('int')),
        );
    }

    public function testToMapOf()
    {
        $set = Set::ints(1, 2, 3);
        $map = $set->toMapOf('string', 'int', fn($i) => yield (string) $i => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(3, $map);
        $this->assertSame(1, $map->get('1'));
        $this->assertSame(2, $map->get('2'));
        $this->assertSame(3, $map->get('3'));
    }
}
