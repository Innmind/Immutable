<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Set;

use Innmind\Immutable\{
    Set\Defer,
    Set\Implementation,
    Set,
    Map,
    Str,
    Sequence,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class DeferTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Implementation::class,
            new Defer('int', (function() {
                yield;
            })()),
        );
    }

    public function testThrowWhenYieldingInvalidType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

        $sequence = new Defer('int', (function() {
            yield '1';
        })());
        \iterator_to_array($sequence->iterator());
    }

    public function testType()
    {
        $this->assertSame(
            'int',
            (new Defer('int', (function() {
                yield;
            })()))->type()
        );
    }

    public function testSize()
    {
        $set = new Defer('int', (function() {
            yield 1;
            yield 2;
        })());

        $this->assertSame(2, $set->size());
        $this->assertSame(2, $set->count());
    }

    public function testIterator()
    {
        $set = new Defer('int', (function() {
            yield 1;
            yield 2;
        })());

        $this->assertSame([1, 2], \iterator_to_array($set->iterator()));
    }

    public function testIntersect()
    {
        $aLoaded = false;
        $bLoaded = false;
        $a = new Defer('int', (function() use (&$aLoaded) {
            yield 1;
            yield 2;
            $aLoaded = true;
        })());
        $b = new Defer('int', (function() use (&$bLoaded) {
            yield 2;
            yield 3;
            $bLoaded = true;
        })());
        $c = $a->intersect($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Defer::class, $c);
        $this->assertSame([2], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testAdd()
    {
        $loaded = false;
        $a = new Defer('int', (function() use (&$loaded) {
            yield 1;
            $loaded = true;
        })());
        $b = ($a)(2);

        $this->assertFalse($loaded);
        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([1, 2], \iterator_to_array($b->iterator()));
        $this->assertSame([1, 2], \iterator_to_array(($b)(2)->iterator()));
        $this->assertTrue($loaded);
    }

    public function testContains()
    {
        $set = new Defer('int', (function() {
            yield 1;
        })());

        $this->assertTrue($set->contains(1));
        $this->assertFalse($set->contains(2));
    }

    public function testRemove()
    {
        $a = new Defer('int', (function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        $b = $a->remove(3);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([1, 2, 4], \iterator_to_array($b->iterator()));
        $this->assertSame($a, $a->remove(5));
    }

    public function testDiff()
    {
        $aLoaded = false;
        $a = new Defer('int', (function() use (&$aLoaded) {
            yield 1;
            yield 2;
            yield 3;
            $aLoaded = true;
        })());
        $bLoaded = false;
        $b = new Defer('int', (function() use (&$bLoaded) {
            yield 2;
            yield 4;
            $bLoaded = true;
        })());
        $c = $a->diff($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Defer::class, $c);
        $this->assertSame([1, 3], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testEquals()
    {
        $a = new Defer('int', (function() {
            yield 1;
            yield 2;
        })());
        $aBis = new Defer('int', (function() {
            yield 1;
            yield 2;
        })());
        $b = new Defer('int', (function() {
            yield 1;
        })());
        $c = new Defer('int', (function() {
            yield 1;
            yield 2;
            yield 3;
        })());

        $this->assertTrue($a->equals($a));
        $this->assertTrue($a->equals($aBis));
        $this->assertFalse($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function testFilter()
    {
        $loaded = false;
        $a = new Defer('int', (function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        })());
        $b = $a->filter(fn($i) => $i % 2 === 0);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([2, 4], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testForeach()
    {
        $set = new Defer('int', (function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
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
        $set = new Defer('int', (function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        $groups = $set->groupBy(fn($i) => $i % 2);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertTrue($groups->isOfType('int', Set::class));
        $this->assertCount(2, $groups);
        $this->assertTrue($groups->get(0)->isOfType('int'));
        $this->assertTrue($groups->get(1)->isOfType('int'));
        $this->assertSame([2, 4], unwrap($groups->get(0)));
        $this->assertSame([1, 3], unwrap($groups->get(1)));
    }

    public function testMap()
    {
        $loaded = false;
        $a = new Defer('int', (function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());
        $b = $a->map(fn($i) => $i * 2);

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Defer::class, $b);
        $this->assertSame([2, 4, 6], \iterator_to_array($b->iterator()));
        $this->assertTrue($loaded);
    }

    public function testThrowWhenTryingToModifyTheTypeWhenMapping()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

        $set = new Defer('int', (function() {
            yield 1;
        })());

        \iterator_to_array($set->map(fn($i) => (string) $i)->iterator());
    }

    public function testPartition()
    {
        $set = new Defer('int', (function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());
        $groups = $set->partition(fn($i) => $i % 2 === 0);

        $this->assertSame([1, 2, 3, 4], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Map::class, $groups);
        $this->assertTrue($groups->isOfType('bool', Set::class));
        $this->assertCount(2, $groups);
        $this->assertTrue($groups->get(true)->isOfType('int'));
        $this->assertTrue($groups->get(false)->isOfType('int'));
        $this->assertSame([2, 4], unwrap($groups->get(true)));
        $this->assertSame([1, 3], unwrap($groups->get(false)));
    }

    public function testSort()
    {
        $loaded = false;
        $set = new Defer('int', (function() use (&$loaded) {
            yield 1;
            yield 4;
            yield 3;
            yield 2;
            $loaded = true;
        })());
        $sorted = $set->sort(fn($a, $b) => $a > $b);

        $this->assertFalse($loaded);
        $this->assertSame([1, 4, 3, 2], \iterator_to_array($set->iterator()));
        $this->assertInstanceOf(Sequence::class, $sorted);
        $this->assertSame([1, 2, 3, 4], unwrap($sorted));
        $this->assertTrue($loaded);
    }

    public function testMerge()
    {
        $aLoaded = false;
        $a = new Defer('int', (function() use (&$aLoaded) {
            yield 1;
            yield 2;
            $aLoaded = true;
        })());
        $bLoaded = false;
        $b = new Defer('int', (function() use (&$bLoaded) {
            yield 2;
            yield 3;
            $bLoaded = true;
        })());
        $c = $a->merge($b);

        $this->assertFalse($aLoaded);
        $this->assertFalse($bLoaded);
        $this->assertSame([1, 2], \iterator_to_array($a->iterator()));
        $this->assertSame([2, 3], \iterator_to_array($b->iterator()));
        $this->assertInstanceOf(Defer::class, $c);
        $this->assertSame([1, 2, 3], \iterator_to_array($c->iterator()));
        $this->assertTrue($aLoaded);
        $this->assertTrue($bLoaded);
    }

    public function testReduce()
    {
        $set = new Defer('int', (function() {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
        })());

        $this->assertSame(10, $set->reduce(0, fn($sum, $i) => $sum + $i));
    }

    public function testClear()
    {
        $a = new Defer('int', (function() {
            yield 1;
        })());
        $b = $a->clear();

        $this->assertSame([1], \iterator_to_array($a->iterator()));
        $this->assertInstanceOf(Implementation::class, $b);
        $this->assertSame([], \iterator_to_array($b->iterator()));
    }

    public function testEmpty()
    {
        $a = new Defer('int', (function() {
            yield 1;
        })());
        $b = new Defer('int', (function() {
            if (false) {
                yield 1;
            }
        })());

        $this->assertTrue($b->empty());
        $this->assertFalse($a->empty());
    }

    public function testToSequenceOf()
    {
        $loaded = false;
        $set = new Defer('int', (function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());
        $sequence = $set->toSequenceOf('string|int', function($i) {
            yield (string) $i;
            yield $i;
        });

        $this->assertFalse($loaded);
        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(
            ['1', 1, '2', 2, '3', 3],
            unwrap($sequence),
        );
        $this->assertTrue($loaded);
    }

    public function testToSetOf()
    {
        $loaded = false;
        $set = new Defer('int', (function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());
        $set = $set->toSetOf('string|int', function($i) {
            yield (string) $i;
            yield $i;
        });

        $this->assertFalse($loaded);
        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(
            ['1', 1, '2', 2, '3', 3],
            unwrap($set),
        );
        $this->assertTrue($loaded);
    }

    public function testToMapOf()
    {
        $set = new Defer('int', (function() {
            yield 1;
            yield 2;
            yield 3;
        })());
        $map = $set->toMapOf('string', 'int', fn($i) => yield (string) $i => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(3, $map);
        $this->assertSame(1, $map->get('1'));
        $this->assertSame(2, $map->get('2'));
        $this->assertSame(3, $map->get('3'));
    }
}
