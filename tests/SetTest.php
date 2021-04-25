<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Set,
    Map,
    Str,
    Sequence,
    Exception\NoElementMatchingPredicateFound,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(\Countable::class, Set::of());
    }

    public function testOf()
    {
        $this->assertTrue(
            Set::of(1, 1, 2, 3)->equals(
                Set::of()
                    ->add(1)
                    ->add(2)
                    ->add(3)
            )
        );
    }

    public function testDefer()
    {
        $loaded = false;
        $set = Set::defer((static function() use (&$loaded) {
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

    public function testLazy()
    {
        $loaded = false;
        $set = Set::lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        });

        $this->assertInstanceOf(Set::class, $set);
        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], unwrap($set));
        $this->assertTrue($loaded);
    }

    public function testMixed()
    {
        $set = Set::mixed(1, '2', 3, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame([1, '2', 3], unwrap($set));
    }

    public function testInts()
    {
        $set = Set::ints(1, 2, 3, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame([1, 2, 3], unwrap($set));
    }

    public function testFloats()
    {
        $set = Set::floats(1, 2, 3.2, 1);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame([1.0, 2.0, 3.2], unwrap($set));
    }

    public function testStrings()
    {
        $set = Set::strings('1', '2', '3', '1');

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(['1', '2', '3'], unwrap($set));
    }

    public function testObjects()
    {
        $a = new \stdClass;
        $b = new \stdClass;
        $c = new \stdClass;
        $set = Set::objects($a, $b, $c, $a);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame([$a, $b, $c], unwrap($set));
    }

    public function testAdd()
    {
        $this->assertSame(0, Set::of()->size());

        $s = Set::of()->add(42);

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

    public function testIntersect()
    {
        $s = Set::of()
            ->add(24)
            ->add(42)
            ->add(66);

        $s2 = $s->intersect(Set::of()->add(42));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame([24, 42, 66], unwrap($s));
        $this->assertSame([42], unwrap($s2));
    }

    public function testContains()
    {
        $s = Set::of();

        $this->assertFalse($s->contains(42));
        $s = $s->add(42);
        $this->assertTrue($s->contains(42));
    }

    public function testRemove()
    {
        $s = Set::of()
            ->add(24)
            ->add(42)
            ->add(66)
            ->add(90)
            ->add(114);

        $s2 = $s->remove(42);
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame([24, 42, 66, 90, 114], unwrap($s));
        $this->assertSame([24, 66, 90, 114], unwrap($s2));
        $this->assertSame([42, 66, 90, 114], unwrap($s->remove(24)));
        $this->assertSame([24, 42, 90, 114], unwrap($s->remove(66)));
        $this->assertSame([24, 42, 66, 114], unwrap($s->remove(90)));
        $this->assertSame([24, 42, 66, 90], unwrap($s->remove(114)));
    }

    public function testDiff()
    {
        $s = Set::of()
            ->add(24)
            ->add(42)
            ->add(66);

        $s2 = $s->diff(Set::of()->add(42));
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame([24, 42, 66], unwrap($s));
        $this->assertSame([24, 66], unwrap($s2));
    }

    public function testEquals()
    {
        $s = Set::of()
            ->add(24)
            ->add(42)
            ->add(66);

        $this->assertTrue(
            $s->equals(
                Set::of()
                    ->add(24)
                    ->add(66)
                    ->add(42)
            )
        );
        $this->assertTrue(Set::of()->equals(Set::of()));
        $this->assertFalse(
            $s->equals(
                Set::of()
                    ->add(24)
                    ->add(66)
            )
        );
    }

    public function testFilter()
    {
        $s = Set::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->filter(static function(int $v) {
            return $v % 2 === 0;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertSame([2, 4], unwrap($s2));
    }

    public function testForeach()
    {
        $s = Set::of()
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

    public function testGroup()
    {
        $s = Set::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $m = $s->group('int', static function(int $v) {
            return $v % 2;
        });
        $this->assertInstanceOf(Map::class, $m);
        $this->assertSame([1, 0], unwrap($m->keys()));
        $this->assertSame([1, 3], unwrap($m->get(1)));
        $this->assertSame([2, 4], unwrap($m->get(0)));

        $groups = Set::ints()->group('string', static fn() => '');

        $this->assertTrue($groups->empty());
    }

    public function testGroupBy()
    {
        $s = Set::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $m = $s->groupBy(static function(int $v) {
            return $v % 2;
        });
        $this->assertInstanceOf(Map::class, $m);
        $this->assertSame([1, 0], unwrap($m->keys()));
        $this->assertSame([1, 3], unwrap($m->get(1)));
        $this->assertSame([2, 4], unwrap($m->get(0)));
    }

    public function testMap()
    {
        $s = Set::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->map(static function(int $v) {
            return $v**2;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Set::class, $s2);
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertSame([1, 4, 9, 16], unwrap($s2));
    }

    public function testMapTo()
    {
        $a = Set::ints(1, 2, 3, 4);
        $b = $a->mapTo('string', static fn($i) => (string) $i);

        $this->assertInstanceOf(Set::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame(['1', '2', '3', '4'], unwrap($b));
    }

    public function testPartition()
    {
        $s = Set::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->partition(static function(int $v) {
            return $v % 2 === 0;
        });
        $this->assertNotSame($s, $s2);
        $this->assertInstanceOf(Map::class, $s2);
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertInstanceOf(Set::class, $s2->get(true));
        $this->assertInstanceOf(Set::class, $s2->get(false));
        $this->assertSame([2, 4], unwrap($s2->get(true)));
        $this->assertSame([1, 3], unwrap($s2->get(false)));
    }

    public function testSort()
    {
        $s = Set::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $s2 = $s->sort(static function(int $a, int $b) {
            return ($a < $b) ? 1 : -1;
        });
        $this->assertInstanceOf(Sequence::class, $s2);
        $this->assertSame([1, 2, 3, 4], unwrap($s));
        $this->assertSame([4, 3, 2, 1], unwrap($s2));
    }

    public function testMerge()
    {
        $s = Set::of()
            ->add(24)
            ->add(42)
            ->add(66);

        $this->assertTrue(
            $s
                ->merge(
                    Set::of()
                        ->add(24)
                        ->add(42)
                        ->add(66)
                )
                ->equals($s)
        );
        $this->assertSame(
            [24, 42, 66, 90, 114],
            unwrap($s->merge(
                Set::of()
                    ->add(90)
                    ->add(114)
            )),
        );
        $this->assertSame([24, 42, 66], unwrap($s));
    }

    public function testReduce()
    {
        $s = Set::of()
            ->add(4)
            ->add(3)
            ->add(2);

        $v = $s->reduce(
            42,
            static function(float $carry, int $value): float {
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
            unwrap(Set::of()
                ->add('foo')
                ->add(42)
                ->add(42.1)
                ->add(true)
                ->add([]))
        );
    }

    public function testEmpty()
    {
        $this->assertTrue(Set::of()->empty());
        $this->assertFalse(Set::of(1)->empty());
    }

    public function testToSequenceOf()
    {
        $set = Set::ints(1, 2, 3);
        $sequence = $set->toSequenceOf('string|int', static function($i) {
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
        $set = $initial->toSetOf('string|int', static function($i) {
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
        $map = $set->toMapOf('string', 'int', static fn($i) => yield (string) $i => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(3, $map);
        $this->assertSame(1, $map->get('1'));
        $this->assertSame(2, $map->get('2'));
        $this->assertSame(3, $map->get('3'));
    }

    public function testFind()
    {
        $sequence = Set::ints(1, 2, 3);

        $this->assertSame(1, $sequence->find(static fn($i) => $i === 1));
        $this->assertSame(2, $sequence->find(static fn($i) => $i === 2));
        $this->assertSame(3, $sequence->find(static fn($i) => $i === 3));

        $this->expectException(NoElementMatchingPredicateFound::class);

        $sequence->find(static fn($i) => $i === 0);
    }

    public function testMatches()
    {
        $set = Set::ints(1, 2, 3);

        $this->assertTrue($set->matches(static fn($i) => $i % 1 === 0));
        $this->assertFalse($set->matches(static fn($i) => $i % 2 === 0));
    }

    public function testAny()
    {
        $set = Set::ints(1, 2, 3);

        $this->assertTrue($set->any(static fn($i) => $i === 2));
        $this->assertFalse($set->any(static fn($i) => $i === 0));
    }
}
