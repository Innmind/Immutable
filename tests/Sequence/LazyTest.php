<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Sequence,
    Sequence\Lazy,
    Sequence\Implementation,
    Map,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class LazyTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Implementation::class,
            new Lazy(static fn() => yield),
        );
    }

    public function testGeneratorNotLoadedAtInstanciation()
    {
        $loaded = false;
        $sequence = new Lazy(static function() use (&$loaded) {
            yield 1;
            $loaded = true;
        });

        $this->assertFalse($loaded);
    }

    public function testSize()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 1;
        });

        $this->assertSame(2, $sequence->size());
        $this->assertSame(2, $sequence->count());
    }

    public function testIterator()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertSame(
            [1, 2, 3],
            \iterator_to_array($sequence->iterator()),
        );
    }

    public function testGet()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 42;
            yield 3;
        });

        $this->assertSame(42, $this->get($sequence, 1));
    }

    public function testReturnNothingWhenIndexNotFound()
    {
        $sequence = new Lazy(static function() {
            if (false) {
                yield 1;
            }
        });

        $this->assertNull(
            $sequence->get(0)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testDiff()
    {
        $aLoaded = false;
        $bLoaded = false;
        $a = new Lazy(static function() use (&$aLoaded) {
            yield 1;
            yield 2;
            $aLoaded = true;
        });
        $b = new Lazy(static function() use (&$bLoaded) {
            yield 2;
            yield 3;
            $bLoaded = true;
        });
        $c = $a->diff($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Lazy::class, $c);
        $this->assertSame([1], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testDistinct()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 1;
            $loaded = true;
        });
        $b = $a->distinct();

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testDrop()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        });
        $b = $a->drop(2);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testDropEnd()
    {
        $a = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        });
        $b = $a->dropEnd(2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Implementation::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
    }

    public function testEquals()
    {
        $a = new Lazy(static function() {
            yield 1;
            yield 2;
        });
        $b = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertTrue($a->equals($a));
        $this->assertFalse($a->equals($b));
    }

    public function testFilter()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        });
        $b = $a->filter(static fn($i) => $i % 2 === 0);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testForeach()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        });
        $calls = 0;
        $sum = 0;

        $this->assertInstanceOf(
            SideEffect::class,
            $sequence->foreach(static function($i) use (&$calls, &$sum) {
                ++$calls;
                $sum += $i;
            }),
        );
        $this->assertSame(4, $calls);
        $this->assertSame(10, $sum);
    }

    public function testGroupEmptySequence()
    {
        $sequence = new Lazy(static function() {
            if (false) {
                yield 1;
            }
        });

        $this->assertTrue(
            $sequence
                ->groupBy(static fn($i) => $i)
                ->equals(Map::of()),
        );
    }

    public function testGroupBy()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        });
        $groups = $sequence->groupBy(static fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($sequence->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame([2, 4], $this->get($groups, 0)->toList());
        $this->assertSame([1, 3], $this->get($groups, 1)->toList());
    }

    public function testReturnNothingWhenTryingToAccessFirstElementOnEmptySequence()
    {
        $sequence = new Lazy(static function() {
            if (false) {
                yield 1;
            }
        });

        $this->assertNull(
            $sequence->first()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenTryingToAccessLastElementOnEmptySequence()
    {
        $sequence = new Lazy(static function() {
            if (false) {
                yield 1;
            }
        });

        $this->assertNull(
            $sequence->last()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testFirst()
    {
        $sequence = new Lazy(static function() {
            yield 2;
            yield 3;
            yield 4;
        });

        $this->assertSame(
            2,
            $sequence->first()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testLast()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertSame(
            3,
            $sequence->last()->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testLastWhenNull()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
            yield null;
        });

        $this->assertNull(
            $sequence->last()->match(
                static fn($value) => $value,
                static fn() => false,
            ),
        );
    }

    public function testContains()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
        });

        $this->assertTrue($sequence->contains(2));
        $this->assertFalse($sequence->contains(4));
    }

    public function testIndexOf()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 4;
        });

        $this->assertSame(
            1,
            $sequence->indexOf(2)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(
            2,
            $sequence->indexOf(4)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testReturnNothingWhenTryingToAccessIndexOfUnknownValue()
    {
        $sequence = new Lazy(static function() {
            if (false) {
                yield 1;
            }
        });

        $this->assertNull(
            $sequence->indexOf(1)->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
    }

    public function testIndices()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield '1';
            yield '2';
            $loaded = true;
        });
        $b = $a->indices();

        $this->assertFalse($loaded);
        $this->assertSame(['1', '2'], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([0, 1], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testIndicesOnEmptySequence()
    {
        $a = new Lazy(static function() {
            if (false) {
                yield 1;
            }
        });
        $b = $a->indices();

        $this->assertSame([], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([], \iterator_to_array($b->iterator()));
    }

    public function testMap()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        });
        $b = $a->map(static fn($i) => $i * 2);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([2, 4, 6], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testPad()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            $loaded = true;
        });
        $b = $a->pad(4, 0);
        $c = $a->pad(1, 0);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertInstanceOf(Lazy::class, $c);
        $this->assertSame([1, 2, 0, 0], \iterator_to_array($b->iterator()));
        $this->assertSame([1, 2], \iterator_to_array($c->iterator()));
        $this->assertTrue($loaded);
    }

    public function testPartition()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        });
        $partition = $sequence->partition(static fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($sequence->iterator()));
        $this->assertInstanceOf(Map::class, $partition);
        $this->assertCount(2, $partition);
        $this->assertSame([2, 4], $this->get($partition, true)->toList());
        $this->assertSame([1, 3], $this->get($partition, false)->toList());
    }

    public function testSlice()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 2;
            yield 3;
            yield 4;
            yield 5;
            $loaded = true;
        });
        $b = $a->slice(1, 3);

        $this->assertFalse($loaded);
        $this->assertSame([2, 3, 4, 5], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
        $this->assertSame(
            [],
            \iterator_to_array($a->slice(0, 0)->iterator()),
        );
        $this->assertSame(
            [2],
            \iterator_to_array($a->slice(0, 1)->iterator()),
        );
        $this->assertSame(
            [5],
            \iterator_to_array($a->slice(3, 6)->iterator()),
        );
    }

    public function testTake()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        });
        $b = $a->take(2);

        $this->assertFalse($loaded);
        $this->assertSame([2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testSequenceNotCompletelyLoadedWhenTakingFewerThanItsSize()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        });
        $b = $a->take(2);

        $this->assertFalse($loaded);
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertFalse($loaded);
    }

    public function testTakeEnd()
    {
        $a = new Lazy(static function() {
            yield 2;
            yield 3;
            yield 4;
        });
        $b = $a->takeEnd(2);

        $this->assertSame([2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Implementation::class, $b);
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
    }

    public function testAppend()
    {
        $aLoaded = false;
        $bLoaded = false;
        $a = new Lazy(static function() use (&$aLoaded) {
            yield 1;
            yield 2;
            $aLoaded = true;
        });
        $b = new Lazy(static function() use (&$bLoaded) {
            yield 3;
            yield 4;
            $bLoaded = true;
        });
        $c = $a->append($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([3, 4], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Lazy::class, $c);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testIntersect()
    {
        $aLoaded = false;
        $bLoaded = false;
        $a = new Lazy(static function() use (&$aLoaded) {
            yield 1;
            yield 2;
            $aLoaded = true;
        });
        $b = new Lazy(static function() use (&$bLoaded) {
            yield 2;
            yield 3;
            $bLoaded = true;
        });
        $c = $a->intersect($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Lazy::class, $c);
        $this->assertSame([2], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testAdd()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 1;
            $loaded = true;
        });
        $b = ($a)(2);

        $this->assertFalse($loaded);
        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testSort()
    {
        $a = new Lazy(static function() {
            yield 1;
            yield 4;
            yield 3;
            yield 2;
        });
        $b = $a->sort(static fn($a, $b) => $a > $b ? 1 : -1);

        $this->assertSame([1, 4, 3, 2], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Lazy::class, $b);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($b->iterator()));
    }

    public function testReduce()
    {
        $sequence = new Lazy(static function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        });

        $this->assertSame(10, $sequence->reduce(0, static fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $loaded = false;
        $a = new Lazy(static function() use (&$loaded) {
            yield 1;
            yield 2;
            $loaded = true;
        });
        $b = $a->clear();

        $this->assertFalse($loaded);
        $this->assertInstanceOf(Implementation::class, $b);
        $this->assertSame([], \iterator_to_array($b->iterator()));
        $this->assertFalse($loaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
    }

    public function testReverse()
    {
        $loaded = false;
        $a = Sequence::lazy(static function() use (&$loaded) {
            yield from [1, 2];
            yield from [3, 4];
            $loaded = true;
        });
        $b = $a->reverse();

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3, 4], $a->toList());
        $this->assertInstanceOf(Sequence::class, $b);
        $this->assertSame([4, 3, 2, 1], $b->toList());
        $this->assertTrue($loaded);
    }

    public function testEmpty()
    {
        $aLoaded = false;
        $bLoaded = false;
        $a = new Lazy(static function() use (&$aLoaded) {
            yield 1;
            $aLoaded = true;
        });
        $b = new Lazy(static function() use (&$bLoaded) {
            if (false) {
                yield 1;
            }
            $bLoaded = true;
        });

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertTrue($b->empty());
        $this->assertFalse($a->empty());
        $this->assertFalse($aLoaded); // still false as we don't need to load the full iterator to know if it's empty
        $this->assertTrue($bLoaded);
    }

    public function testFind()
    {
        $count = 0;
        $sequence = new Lazy(static function() use (&$count) {
            ++$count;
            yield 1;
            ++$count;
            yield 2;
            ++$count;
            yield 3;
        });

        $this->assertSame(
            1,
            $sequence->find(static fn($i) => $i === 1)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(1, $count);
        $this->assertSame(
            2,
            $sequence->find(static fn($i) => $i === 2)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(3, $count);
        $this->assertSame(
            3,
            $sequence->find(static fn($i) => $i === 3)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
        $this->assertSame(6, $count);

        $this->assertNull(
            $sequence->find(static fn($i) => $i === 0)->match(
                static fn($i) => $i,
                static fn() => null,
            ),
        );
    }

    public function testCallCleanupWhenGettingIndexBeforeTheEndOfGenerator()
    {
        $cleanupCalled = false;
        $sequence = new Lazy(static function($registerCleanup) use (&$cleanupCalled) {
            $registerCleanup(static function() use (&$cleanupCalled) {
                $cleanupCalled = true;
            });
            yield 2;
            yield 3;
            yield 4;
            yield 5;
        });
        $this->assertFalse($cleanupCalled);
        $value = $sequence->get(1)->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertSame(3, $value);
        $this->assertTrue($cleanupCalled);
    }

    public function testCallCleanupWhenElementBeingLookedForIsFoundBeforeTheEndOfGenerator()
    {
        $cleanupCalled = false;
        $sequence = new Lazy(static function($registerCleanup) use (&$cleanupCalled) {
            $registerCleanup(static function() use (&$cleanupCalled) {
                $cleanupCalled = true;
            });
            yield 2;
            yield 3;
            yield 4;
            yield 5;
        });

        $this->assertFalse($cleanupCalled);
        $this->assertTrue($sequence->contains(3));
        $this->assertTrue($cleanupCalled);
    }

    public function testCallCleanupWhenIndexOfElementBeingLookedForIsFoundBeforeTheEndOfGenerator()
    {
        $cleanupCalled = false;
        $sequence = new Lazy(static function($registerCleanup) use (&$cleanupCalled) {
            $registerCleanup(static function() use (&$cleanupCalled) {
                $cleanupCalled = true;
            });
            yield 2;
            yield 3;
            yield 4;
            yield 5;
        });

        $this->assertFalse($cleanupCalled);
        $index = $sequence->indexOf(3)->match(
            static fn($index) => $index,
            static fn() => null,
        );
        $this->assertSame(1, $index);
        $this->assertTrue($cleanupCalled);
    }

    public function testCallCleanupWhenTakingLessElementsThanContainedInTheGenerator()
    {
        $cleanupCalled = false;
        $sequence = new Lazy(static function($registerCleanup) use (&$cleanupCalled) {
            $registerCleanup(static function() use (&$cleanupCalled) {
                $cleanupCalled = true;
            });
            yield 2;
            yield 3;
            yield 4;
            yield 5;
        });

        $this->assertFalse($cleanupCalled);
        $this->assertSame([2, 3], \iterator_to_array($sequence->take(2)->iterator()));
        $this->assertTrue($cleanupCalled);
    }

    public function testCallCleanupWhenFindingElementBeforeTheEndOfGenerator()
    {
        $cleanupCalled = false;
        $sequence = new Lazy(static function($registerCleanup) use (&$cleanupCalled) {
            $registerCleanup(static function() use (&$cleanupCalled) {
                $cleanupCalled = true;
            });
            yield 2;
            yield 3;
            yield 4;
            yield 5;
        });
        $this->assertFalse($cleanupCalled);
        $value = $sequence->find(static fn($value) => $value === 3)->match(
            static fn($value) => $value,
            static fn() => null,
        );

        $this->assertSame(3, $value);
        $this->assertTrue($cleanupCalled);
    }

    public function get($map, $index)
    {
        return $map->get($index)->match(
            static fn($value) => $value,
            static fn() => null,
        );
    }
}
