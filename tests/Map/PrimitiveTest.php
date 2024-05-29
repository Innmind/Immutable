<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map\Primitive,
    Map\Implementation,
    Map,
    Set,
    Sequence,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $m = new Primitive;

        $this->assertInstanceOf(Implementation::class, $m);
        $this->assertInstanceOf(\Countable::class, $m);
    }

    public function testPut()
    {
        $m = new Primitive;

        $this->assertSame(0, $m->size());
        $m2 = ($m)(42, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new Primitive;
        $m = $m
            (23, 24)
            (41, 42)
            (65, 66)
            (89, 90)
            (65, 1);

        $this->assertSame(24, $this->get($m, 23));
        $this->assertSame(42, $this->get($m, 41));
        $this->assertSame(1, $this->get($m, 65));
        $this->assertSame(90, $this->get($m, 89));
        $this->assertSame(4, $m->size());
    }

    public function testGet()
    {
        $m = new Primitive;
        $m = ($m)(23, 24);

        $this->assertSame(24, $this->get($m, 23));
    }

    public function testReturnNothingWhenGettingUnknownKey()
    {
        $this->assertNull($this->get(new Primitive, 24));
    }

    public function testContains()
    {
        $m = new Primitive;
        $m = ($m)(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testClear()
    {
        $m = new Primitive;
        $m = ($m)(24, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
    }

    public function testEquals()
    {
        $m = (new Primitive)(24, 42);
        $m2 = (new Primitive)(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals(($m2)(65, 66)));
        $this->assertFalse($m->equals(($m2)(24, 24)));
        $this->assertFalse(
            (new Primitive)('foo_res', 'res')('foo_bar_res', 'res')->equals(
                (new Primitive)
                    ('foo_res', 'res')
                    ('bar_res', 'res'),
            ),
        );

        $m = (new Primitive)
            (24, 42)
            (42, 24);
        $m2 = (new Primitive)
            (42, 24)
            (24, 42);

        $this->assertTrue($m->equals($m2));

        $this->assertTrue((new Primitive)->equals(new Primitive));
    }

    public function testFilter()
    {
        $m = (new Primitive)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5);

        $m2 = $m->filter(static function(int $key, int $value) {
            return ($key + $value) % 3 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(4));
        $this->assertFalse($m2->contains(0));
        $this->assertFalse($m2->contains(2));
    }

    public function testForeach()
    {
        $m = (new Primitive)
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
            (new Primitive)
                ->groupBy(static function() {})
                ->equals(Map::of()),
        );
    }

    public function testGroupBy()
    {
        $m = (new Primitive)
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
        $this->assertSame(2, $this->get($m2, 0)->size());
        $this->assertSame(1, $this->get($m2, 1)->size());
        $this->assertSame(1, $this->get($m2, 2)->size());
        $this->assertSame(1, $this->get($this->get($m2, 1), 0));
        $this->assertSame(2, $this->get($this->get($m2, 0), 1));
        $this->assertSame(3, $this->get($this->get($m2, 2), 2));
        $this->assertSame(5, $this->get($this->get($m2, 0), 4));
    }
    public function testKeys()
    {
        $m = (new Primitive)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame([0, 1, 2, 4], $k->toList());
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = (new Primitive)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5)
            (5, 5);

        $v = $m->values();
        $this->assertInstanceOf(Sequence::class, $v);
        $this->assertSame([1, 2, 3, 5, 5], $v->toList());
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = (new Primitive)
            (0, 1)
            (1, 2)
            (2, 3)
            (4, 5);

        $m2 = $m->map(static function(int $key, int $value) {
            return $value**2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame([0, 1, 2, 4], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 5], $m->values()->toList());
        $this->assertSame([0, 1, 2, 4], $m2->keys()->toList());
        $this->assertSame([1, 4, 9, 25], $m2->values()->toList());
    }

    public function testRemove()
    {
        $m = (new Primitive)
            (0, 1)
            (1, 2)
            (2, 3)
            (3, 4)
            (4, 5);

        $m2 = $m->remove(12);
        $this->assertSame($m, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());

        $m2 = $m->remove(3);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());
        $this->assertSame([0, 1, 2, 4], $m2->keys()->toList());
        $this->assertSame([1, 2, 3, 5], $m2->values()->toList());

        $m2 = $m->remove(4);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());
        $this->assertSame([0, 1, 2, 3], $m2->keys()->toList());
        $this->assertSame([1, 2, 3, 4], $m2->values()->toList());

        $m2 = $m->remove(0);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());
        $this->assertSame([1, 2, 3, 4], $m2->keys()->toList());
        $this->assertSame([2, 3, 4, 5], $m2->values()->toList());
    }

    public function testMerge()
    {
        $m = (new Primitive)
            ($s = 90, 24)
            ($s2 = 91, 42);
        $m2 = (new Primitive)
            ($s3 = 92, 24)
            ($s2, 66)
            ($s4 = 93, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(Primitive::class, $m3);
        $this->assertSame(4, $m3->size());
        $this->assertSame([$s, $s2], $m->keys()->toList());
        $this->assertSame([24, 42], $m->values()->toList());
        $this->assertSame([$s3, $s2, $s4], $m2->keys()->toList());
        $this->assertSame([24, 66, 42], $m2->values()->toList());
        $this->assertSame([$s, $s2, $s3, $s4], $m3->keys()->toList());
        $this->assertSame([24, 66, 24, 42], $m3->values()->toList());
        $this->assertFalse($m3->equals($m2->merge($m)));
    }

    public function testPartition()
    {
        $m = (new Primitive)
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
            $p->keys()->toList(),
        );
        $this->assertSame(
            [1, 4],
            $this->get($p, true)->keys()->toList(),
        );
        $this->assertSame(
            [2, 5],
            $this->get($p, true)->values()->toList(),
        );
        $this->assertSame(
            [0, 2, 3],
            $this->get($p, false)->keys()->toList(),
        );
        $this->assertSame(
            [1, 3, 4],
            $this->get($p, false)->values()->toList(),
        );
    }

    public function testReduce()
    {
        $m = (new Primitive)(4, 4);

        $v = $m->reduce(
            42,
            static function(float $carry, int $key, int $value): float {
                return $carry / ($key * $value);
            },
        );

        $this->assertSame(2.625, $v);
        $this->assertSame([4], $m->keys()->toList());
        $this->assertSame([4], $m->values()->toList());
    }

    public function testEmpty()
    {
        $this->assertTrue((new Primitive)->empty());
        $this->assertFalse((new Primitive)(1, 2)->empty());
    }

    public function testWorkAroundPhpImplicitCast()
    {
        $map = (new Primitive)('1', 'foo');

        $this->assertTrue($map->contains('1'));
        $map->foreach(function($key, $value) {
            $this->assertSame('1', $key);
            $this->assertSame('foo', $value);
        });
        $this->assertSame('foo', $this->get($map, '1'));
        $this->assertSame(['1'], $map->keys()->toList());
    }

    public function testFind()
    {
        $map = (new Primitive)(1, 2)(3, 4)(5, 6);

        $this->assertSame(
            4,
            $map
                ->find(static fn($k) => $k === 3)
                ->map(static fn($pair) => $pair->value())
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );
    }

    private function get($map, $index)
    {
        return $map->get($index)->match(
            static fn($value) => $value,
            static fn() => null,
        );
    }
}
