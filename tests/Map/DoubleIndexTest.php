<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map\DoubleIndex,
    Map\Implementation,
    Map,
    Pair,
    Str,
    Set,
    Sequence,
    Exception\ElementNotFound,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class DoubleIndexTest extends TestCase
{
    public function testInterface()
    {
        $m = new DoubleIndex;

        $this->assertInstanceOf(Map\Implementation::class, $m);
        $this->assertInstanceOf(\Countable::class, $m);
    }

    public function testPut()
    {
        $m = new DoubleIndex;

        $this->assertSame(0, $m->size());
        $m2 = ($m)(42, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new DoubleIndex;
        $m = $m
            (23, 24)
            (41, 42)
            (65, 66)
            (89, 90)
            (65, 1);

        $this->assertSame(24, $m->get(23));
        $this->assertSame(42, $m->get(41));
        $this->assertSame(1, $m->get(65));
        $this->assertSame(90, $m->get(89));
        $this->assertSame(4, $m->size());
    }

    public function testGet()
    {
        $m = new DoubleIndex;
        $m = ($m)(23, 24);

        $this->assertSame(24, $m->get(23));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFound::class);

        (new DoubleIndex)->get(24);
    }

    public function testContains()
    {
        $m = new DoubleIndex;
        $m = ($m)(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testClear()
    {
        $m = new DoubleIndex;
        $m = ($m)(24, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
    }

    public function testEquals()
    {
        $m = (new DoubleIndex)(24, 42);
        $m2 = (new DoubleIndex)(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals(($m2)(65, 66)));
        $this->assertFalse($m->equals(($m2)(24, 24)));
        $this->assertFalse(
            (new DoubleIndex)('foo_res', 'res')('foo_bar_res', 'res')->equals(
                (new DoubleIndex)
                    ('foo_res', 'res')
                    ('bar_res', 'res')
            )
        );

        $m = (new DoubleIndex)
            (24, 42)
            (42, 24);
        $m2 = (new DoubleIndex)
            (42, 24)
            (24, 42);

        $this->assertTrue($m->equals($m2));

        $this->assertTrue((new DoubleIndex)->equals(new DoubleIndex));
    }

    public function testFilter()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5);

        $m2 = $m->filter(static function(int $key, int $value) {
            return ($key + $value) % 3 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(4));
        $this->assertFalse($m2->contains(0));
        $this->assertFalse($m2->contains(2));
    }

    public function testForeach()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (3, 4);
        $count = 0;

        $m->foreach(function(int $key, int $value) use (&$count) {
            $this->assertSame($count, $key);
            $this->assertSame($value, $key + 1);
            ++$count;
        });
        $this->assertSame(4, $count);
    }

    public function testGroupEmptyMap()
    {
        $this->assertTrue(
            (new DoubleIndex)
                ->groupBy(static function() {})
                ->equals(Map::of()),
        );
    }

    public function testGroupBy()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5);

        $m2 = $m->groupBy(static function(int $key, int $value) {
            return ($key + $value) % 3;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(2));
        $this->assertSame(2, $m2->get(0)->size());
        $this->assertSame(1, $m2->get(1)->size());
        $this->assertSame(1, $m2->get(2)->size());
        $this->assertSame(1, $m2->get(1)->get(0));
        $this->assertSame(2, $m2->get(0)->get(1));
        $this->assertSame(3, $m2->get(2)->get(2));
        $this->assertSame(5, $m2->get(0)->get(4));
    }
    public function testKeys()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame([0, 1, 2, 4], unwrap($k));
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5)
            (5, 5);

        $v = $m->values();
        $this->assertInstanceOf(Sequence::class, $v);
        $this->assertSame([1, 2, 3, 5, 5], unwrap($v));
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5);

        $m2 = $m->map(static function(int $key, int $value) {
            return $value**2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame([0, 1, 2, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 5], unwrap($m->values()));
        $this->assertSame([0, 1, 2, 4], unwrap($m2->keys()));
        $this->assertSame([1, 4, 9, 25], unwrap($m2->values()));
    }

    public function testRemove()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (3, 4)
            (4, 5);

        $m2 = $m->remove(12);
        $this->assertSame($m, $m2);
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));

        $m2 = $m->remove(3);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([0, 1, 2, 4], unwrap($m2->keys()));
        $this->assertSame([1, 2, 3, 5], unwrap($m2->values()));

        $m2 = $m->remove(4);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([0, 1, 2, 3], unwrap($m2->keys()));
        $this->assertSame([1, 2, 3, 4], unwrap($m2->values()));

        $m2 = $m->remove(0);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([1, 2, 3, 4], unwrap($m2->keys()));
        $this->assertSame([2, 3, 4, 5], unwrap($m2->values()));
    }

    public function testMerge()
    {
        $m = (new DoubleIndex)
            ($s = new \stdClass, 24)
            ($s2 = new \stdClass, 42);
        $m2 = (new DoubleIndex)
            ($s3 = new \stdClass, 24)
            ($s2, 66)
            ($s4 = new \stdClass, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(DoubleIndex::class, $m3);
        $this->assertSame(4, $m3->size());
        $this->assertSame([$s, $s2], unwrap($m->keys()));
        $this->assertSame([24, 42], unwrap($m->values()));
        $this->assertSame([$s3, $s2, $s4], unwrap($m2->keys()));
        $this->assertSame([24, 66, 42], unwrap($m2->values()));
        $this->assertSame([$s, $s2, $s3, $s4], unwrap($m3->keys()));
        $this->assertSame([24, 66, 24, 42], unwrap($m3->values()));
        $this->assertFalse($m3->equals($m2->merge($m)));
    }

    public function testPartition()
    {
        $m = (new DoubleIndex)
            (0, 1)
            (1, 2)
            (2, 3)
            (3, 4)
            (4, 5);

        $p = $m->partition(static function(int $i, int $v) {
            return ($i + $v) % 3 === 0;
        });

        $this->assertInstanceOf(Map::class, $p);
        $this->assertNotSame($p, $m);
        $this->assertSame(
            [true, false],
            unwrap($p->keys()),
        );
        $this->assertSame(
            [1, 4],
            unwrap($p->get(true)->keys()),
        );
        $this->assertSame(
            [2, 5],
            unwrap($p->get(true)->values()),
        );
        $this->assertSame(
            [0, 2, 3],
            unwrap($p->get(false)->keys()),
        );
        $this->assertSame(
            [1, 3, 4],
            unwrap($p->get(false)->values()),
        );
    }

    public function testReduce()
    {
        $m = (new DoubleIndex)(4, 4);

        $v = $m->reduce(
            42,
            static function(float $carry, int $key, int $value): float {
                return $carry / ($key * $value);
            }
        );

        $this->assertSame(2.625, $v);
        $this->assertSame([4], unwrap($m->keys()));
        $this->assertSame([4], unwrap($m->values()));
    }

    public function testEmpty()
    {
        $this->assertTrue((new DoubleIndex)->empty());
        $this->assertFalse((new DoubleIndex)(1, 2)->empty());
    }

    public function testToSequenceOf()
    {
        $map = (new DoubleIndex)
            (1, 2)
            (3, 4);
        $sequence = $map->toSequenceOf('int', static function($k, $v) {
            yield $k;
            yield $v;
        });

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(
            [1, 2, 3, 4],
            unwrap($sequence),
        );
    }

    public function testToSetOf()
    {
        $map = (new DoubleIndex)
            (1, 2)
            (3, 4);
        $set = $map->toSetOf('int', static function($k, $v) {
            yield $k;
            yield $v;
        });

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(
            [1, 2, 3, 4],
            unwrap($set),
        );
    }

    public function testToMapOf()
    {
        $map = (new DoubleIndex)
            (1, 2)
            (3, 4);
        $map = $map->toMapOf('string', 'int', static fn($i, $j) => yield (string) $j => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(2, $map);
        $this->assertSame(1, $map->get('2'));
        $this->assertSame(3, $map->get('4'));
    }
}
