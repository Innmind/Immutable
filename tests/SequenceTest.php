<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Sequence,
    Str,
    Set,
    Map,
    Exception\OutOfBoundException,
    Exception\LogicException,
    Exception\NoElementMatchingPredicateFound,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $sequence = Sequence::of();

        $this->assertInstanceOf(\Countable::class, $sequence);
        $this->assertSame([], unwrap($sequence));
    }

    public function testOf()
    {
        $this->assertTrue(
            Sequence::of(1, 2, 3)->equals(
                Sequence::of()
                    ->add(1)
                    ->add(2)
                    ->add(3)
            )
        );
    }

    public function testDefer()
    {
        $loaded = false;
        $sequence = Sequence::defer((static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], unwrap($sequence));
        $this->assertTrue($loaded);
    }

    public function testLazy()
    {
        $loaded = false;
        $sequence = Sequence::lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        });

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], unwrap($sequence));
        $this->assertTrue($loaded);
    }

    public function testMixed()
    {
        $sequence = Sequence::mixed(1, '2', 3);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([1, '2', 3], unwrap($sequence));
    }

    public function testInts()
    {
        $sequence = Sequence::ints(1, 2, 3);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([1, 2, 3], unwrap($sequence));
    }

    public function testFloats()
    {
        $sequence = Sequence::floats(1, 2, 3.2);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([1.0, 2.0, 3.2], unwrap($sequence));
    }

    public function testStrings()
    {
        $sequence = Sequence::strings('1', '2', '3');

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(['1', '2', '3'], unwrap($sequence));
    }

    public function testObjects()
    {
        $a = new \stdClass;
        $b = new \stdClass;
        $c = new \stdClass;
        $sequence = Sequence::objects($a, $b, $c);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame([$a, $b, $c], unwrap($sequence));
    }

    public function testSize()
    {
        $this->assertSame(
            2,
            Sequence::of()
                ->add(1)
                ->add(2)
                ->size()
        );
    }

    public function testCount()
    {
        $this->assertCount(
            2,
            Sequence::of()
                ->add(1)
                ->add(2)
        );
    }

    public function testGet()
    {
        $this->assertSame(
            1,
            Sequence::of()->add(1)->get(0)
        );
    }

    public function testThrowWhenGettingUnknownIndex()
    {
        $this->expectException(OutOfBoundException::class);

        Sequence::of()->get(0);
    }

    public function testDiff()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3);
        $b = Sequence::of()
            ->add(3)
            ->add(4)
            ->add(5);
        $c = $a->diff($b);

        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame([1, 2, 3], unwrap($a));
        $this->assertSame([3, 4, 5], unwrap($b));
        $this->assertSame([1, 2], unwrap($c));
    }

    public function testDistinct()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(1)
            ->add(1);
        $b = $a->distinct();

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 1, 1], unwrap($a));
        $this->assertSame([1], unwrap($b));
    }

    public function testDrop()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->drop(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 3, 5], unwrap($a));
        $this->assertSame([5], unwrap($b));
    }

    public function testDropEnd()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);
        $b = $a->dropEnd(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 3, 5], unwrap($a));
        $this->assertSame([1], unwrap($b));
    }

    public function testEquals()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);
        $b = Sequence::of()
            ->add(1)
            ->add(5);
        $c = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(5);

        $this->assertTrue($a->equals($c));
        $this->assertTrue($a->equals($a));
        $this->assertFalse($a->equals($b));
    }

    public function testFilter()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->filter(static function(int $value): bool {
            return $value % 2 === 0;
        });

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], unwrap($a));
        $this->assertSame([2, 4], unwrap($b));
    }

    public function testForeach()
    {
        $sum = 0;
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->foreach(static function(int $value) use (&$sum) {
                $sum += $value;
            });

        $this->assertSame(10, $sum);
    }

    public function testGroupBy()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $map = $sequence->groupBy(static function(int $value): int {
            return $value % 3;
        });

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(3, $map);
        $this->assertSame([3], unwrap($map->get(0)));
        $this->assertSame([1, 4], unwrap($map->get(1)));
        $this->assertSame([2], unwrap($map->get(2)));
    }

    public function testGroupEmptySequence()
    {
        $this->assertTrue(
            Sequence::of()
                ->groupBy(static function() {})
                ->equals(Map::of()),
        );
    }

    public function testFirst()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(1, $sequence->first());
    }

    public function testLast()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(4, $sequence->last());
    }

    public function testContains()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertTrue($sequence->contains(2));
        $this->assertFalse($sequence->contains(5));
    }

    public function testIndexOf()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);

        $this->assertSame(0, $sequence->indexOf(1));
        $this->assertSame(3, $sequence->indexOf(4));
    }

    public function testIndices()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $indices = $sequence->indices();

        $this->assertInstanceOf(Sequence::class, $indices);
        $this->assertSame([0, 1, 2, 3], unwrap($indices));
    }

    public function testEmptyIndices()
    {
        $sequence = Sequence::of();
        $indices = $sequence->indices();

        $this->assertInstanceOf(Sequence::class, $indices);
        $this->assertSame([], unwrap($indices));
    }

    public function testMap()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->map(static function(int $value): int {
            return $value**2;
        });

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], unwrap($a));
        $this->assertSame([1, 4, 9, 16], unwrap($b));
    }

    public function testFlatMap()
    {
        $sequence = Sequence::of(1, 2, 3, 4);
        $sequence2 = $sequence->flatMap(static fn($i) => Sequence::of($i, $i));

        $this->assertNotSame($sequence, $sequence2);
        $this->assertSame([1, 2, 3, 4], unwrap($sequence));
        $this->assertSame([1, 1, 2, 2, 3, 3, 4, 4], unwrap($sequence2));
    }

    public function testPad()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2);
        $b = $a->pad(4, 0);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2], unwrap($a));
        $this->assertSame([1, 2, 0, 0], unwrap($b));
    }

    public function testPartition()
    {
        $map = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->partition(static function(int $value): bool {
                return $value % 2 === 0;
            });

        $this->assertInstanceOf(Map::class, $map);
        $this->assertSame([2, 4], unwrap($map->get(true)));
        $this->assertSame([1, 3], unwrap($map->get(false)));
    }

    public function testSlice()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->slice(1, 3);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], unwrap($a));
        $this->assertSame([2, 3], unwrap($b));
    }

    public function testSplitAt()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->splitAt(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], unwrap($a));
        $this->assertSame([1, 2], unwrap($b->first()));
        $this->assertSame([3, 4], unwrap($b->last()));
    }

    public function testTake()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->take(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], unwrap($a));
        $this->assertSame([1, 2], unwrap($b));
    }

    public function testTakeEnd()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4);
        $b = $a->takeEnd(2);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 4], unwrap($a));
        $this->assertSame([3, 4], unwrap($b));
    }

    public function testAppend()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2);
        $b = Sequence::of()
            ->add(3)
            ->add(4);
        $c = $b->append($a);

        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame([1, 2], unwrap($a));
        $this->assertSame([3, 4], unwrap($b));
        $this->assertSame([3, 4, 1, 2], unwrap($c));
    }

    public function testIntersect()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2);
        $b = Sequence::of()
            ->add(2)
            ->add(3);
        $c = $b->intersect($a);

        $this->assertInstanceOf(Sequence::class, $c);
        $this->assertNotSame($c, $a);
        $this->assertNotSame($c, $b);
        $this->assertSame([1, 2], unwrap($a));
        $this->assertSame([2, 3], unwrap($b));
        $this->assertSame([2], unwrap($c));
    }

    public function testAdd()
    {
        $a = Sequence::of();
        $b = $a->add(1);

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([], unwrap($a));
        $this->assertSame([1], unwrap($b));

        $this->assertSame(
            [1, 2, 3],
            unwrap(Sequence::ints(1)(2)(3)),
        );
    }

    public function testSort()
    {
        $a = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(3)
            ->add(4);
        $b = $a->sort(static function(int $a, int $b): int {
            return ($b > $a) ? 1 : -1;
        });

        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertNotSame($a, $b);
        $this->assertSame([1, 2, 3, 3, 4], unwrap($a));
        $this->assertSame([4, 3, 3, 2, 1], unwrap($b));
    }

    public function testReduce()
    {
        $value = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3)
            ->add(4)
            ->reduce(
                0,
                static function(int $carry, int $value): int {
                    return $carry + $value;
                }
            );

        $this->assertSame(10, $value);
    }

    public function testClear()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(2)
            ->add(3);
        $sequence2 = $sequence->clear();

        $this->assertNotSame($sequence, $sequence2);
        $this->assertSame([1, 2, 3], unwrap($sequence));
        $this->assertSame([], unwrap($sequence2));
    }

    public function testReverse()
    {
        $sequence = Sequence::of()
            ->add(1)
            ->add(3)
            ->add(4)
            ->add(2);
        $reverse = $sequence->reverse();

        $this->assertInstanceOf(Sequence::class, $reverse);
        $this->assertNotSame($sequence, $reverse);
        $this->assertSame([1, 3, 4, 2], unwrap($sequence));
        $this->assertSame([2, 4, 3, 1], unwrap($reverse));
    }

    public function testEmpty()
    {
        $this->assertTrue(Sequence::of()->empty());
        $this->assertFalse(Sequence::of(1)->empty());
    }

    public function testToSequenceOf()
    {
        $initial = Sequence::ints(1, 2, 3);
        $sequence = $initial->toSequenceOf('string|int', static function($i) {
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
            unwrap($initial->toSequenceOf('int')),
        );
    }

    public function testToSetOf()
    {
        $sequence = Sequence::ints(1, 2, 3);
        $set = $sequence->toSetOf('string|int', static function($i) {
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
            unwrap($sequence->add(1)->toSetOf('int')),
        );
    }

    public function testToMapOf()
    {
        $sequence = Sequence::ints(1, 2, 3);
        $map = $sequence->toMapOf('string', 'int', static fn($i) => yield (string) $i => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(3, $map);
        $this->assertSame(1, $map->get('1'));
        $this->assertSame(2, $map->get('2'));
        $this->assertSame(3, $map->get('3'));
    }

    public function testFind()
    {
        $sequence = Sequence::ints(1, 2, 3);

        $this->assertSame(1, $sequence->find(static fn($i) => $i === 1));
        $this->assertSame(2, $sequence->find(static fn($i) => $i === 2));
        $this->assertSame(3, $sequence->find(static fn($i) => $i === 3));

        $this->expectException(NoElementMatchingPredicateFound::class);

        $sequence->find(static fn($i) => $i === 0);
    }

    public function testMatches()
    {
        $sequence = Sequence::ints(1, 2, 3);

        $this->assertTrue($sequence->matches(static fn($i) => $i % 1 === 0));
        $this->assertFalse($sequence->matches(static fn($i) => $i % 2 === 0));
    }

    public function testAny()
    {
        $sequence = Sequence::ints(1, 2, 3);

        $this->assertTrue($sequence->any(static fn($i) => $i === 2));
        $this->assertFalse($sequence->any(static fn($i) => $i === 0));
    }
}
