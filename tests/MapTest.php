<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Map,
    SizeableInterface,
    Pair,
    Str,
    Set,
    Sequence,
    Exception\ElementNotFound,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testInterface()
    {
        $m = Map::of();

        $this->assertInstanceOf(\Countable::class, $m);
    }

    public function testOf()
    {
        $map = Map::of()
            (1, 1.1)
            (2, 2.1);

        $this->assertTrue(
            $map->equals(
                Map::of()
                    ->put(1, 1.1)
                    ->put(2, 2.1)
            )
        );
    }

    public function testEmptyOf()
    {
        $this->assertTrue(Map::of()->equals(Map::of()));
    }

    public function testPut()
    {
        $m = Map::of();

        $this->assertSame(0, $m->size());
        $m2 = $m->put(42, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = Map::of();
        $m = $m
            ->put(23, 24)
            ->put(41, 42)
            ->put(65, 66)
            ->put(89, 90)
            ->put(65, 1);

        $this->assertSame(24, $m->get(23));
        $this->assertSame(42, $m->get(41));
        $this->assertSame(1, $m->get(65));
        $this->assertSame(90, $m->get(89));
        $this->assertSame(4, $m->size());
    }

    public function testTupleLikeInjection()
    {
        $map = Map::of()
            (1, 2)
            (3, 4);
        $expected = Map::of()
            ->put(1, 2)
            ->put(3, 4);

        $this->assertTrue($map->equals($expected));
    }

    public function testGet()
    {
        $m = Map::of();
        $m = $m->put(23, 24);

        $this->assertSame(24, $m->get(23));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFound::class);

        Map::of()->get(24);
    }

    public function testContains()
    {
        $m = Map::of();
        $m = $m->put(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testClear()
    {
        $m = Map::of();
        $m = $m->put(24, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
    }

    public function testEquals()
    {
        $m = Map::of()->put(24, 42);
        $m2 = Map::of()->put(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals($m2->put(65, 66)));
        $this->assertFalse($m->equals($m2->put(24, 24)));
        $this->assertFalse(
            Map::of()
                ->put('foo_res', 'res')
                ->put('foo_bar_res', 'res')
                ->equals(
                    Map::of()
                        ->put('foo_res', 'res')
                        ->put('bar_res', 'res')
                )
        );

        $m = Map::of()
            ->put(24, 42)
            ->put(42, 24);
        $m2 = Map::of()
            ->put(42, 24)
            ->put(24, 42);

        $this->assertTrue($m->equals($m2));

        $this->assertTrue(Map::of()->equals(Map::of()));
    }

    public function testFilter()
    {
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->filter(static function(int $key, int $value) {
            return ($key + $value) % 3 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(4));
        $this->assertFalse($m2->contains(0));
        $this->assertFalse($m2->contains(2));
    }

    public function testForeach()
    {
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4);
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
            Map::of()
                ->groupBy(static function() {})
                ->equals(Map::of()),
        );
    }

    public function testGroupBy()
    {
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

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
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame([0, 1, 2, 4], unwrap($k));
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5)
            ->put(5, 5);

        $v = $m->values();
        $this->assertInstanceOf(Sequence::class, $v);
        $this->assertSame([1, 2, 3, 5, 5], unwrap($v));
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->map(static function(int $key, int $value) {
            return $value**2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 5], unwrap($m->values()));
        $this->assertSame([0, 1, 2, 4], unwrap($m2->keys()));
        $this->assertSame([1, 4, 9, 25], unwrap($m2->values()));
    }

    public function testFlatMap()
    {
        $map = Map::of()(0, 1)(2, 3)(4, 5);
        $map2 = $map->flatMap(static fn($key, $value) => Map::of()($value, $key));

        $this->assertNotSame($map, $map2);
        $this->assertSame([0, 2, 4], unwrap($map->keys()));
        $this->assertSame([1, 3, 5], unwrap($map->values()));
        $this->assertSame([1, 3, 5], unwrap($map2->keys()));
        $this->assertSame([0, 2, 4], unwrap($map2->values()));
    }

    public function testRemove()
    {
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4)
            ->put(4, 5);

        $m2 = $m->remove(12);
        $this->assertTrue($m->equals($m2));
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));

        $m2 = $m->remove(3);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([0, 1, 2, 4], unwrap($m2->keys()));
        $this->assertSame([1, 2, 3, 5], unwrap($m2->values()));

        $m2 = $m->remove(4);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([0, 1, 2, 3], unwrap($m2->keys()));
        $this->assertSame([1, 2, 3, 4], unwrap($m2->values()));

        $m2 = $m->remove(0);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([1, 2, 3, 4], unwrap($m2->keys()));
        $this->assertSame([2, 3, 4, 5], unwrap($m2->values()));
    }

    public function testMerge()
    {
        $m = Map::of()
            ->put($s = new \stdClass, 24)
            ->put($s2 = new \stdClass, 42);
        $m2 = Map::of()
            ->put($s3 = new \stdClass, 24)
            ->put($s2, 66)
            ->put($s4 = new \stdClass, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(Map::class, $m3);
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
        $m = Map::of()
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4)
            ->put(4, 5);

        $p = $m->partition(static function(int $i, int $v) {
            return ($i + $v) % 3 === 0;
        });

        $this->assertInstanceOf(Map::class, $p);
        $this->assertNotSame($p, $m);
        $this->assertSame(
            [true, false],
            unwrap($p->keys())
        );
        $this->assertSame(
            [1, 4],
            unwrap($p->get(true)->keys())
        );
        $this->assertSame(
            [2, 5],
            unwrap($p->get(true)->values())
        );
        $this->assertSame(
            [0, 2, 3],
            unwrap($p->get(false)->keys())
        );
        $this->assertSame(
            [1, 3, 4],
            unwrap($p->get(false)->values())
        );
    }

    public function testReduce()
    {
        $m = Map::of()->put(4, 4);

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

    public function testMatches()
    {
        $map = Map::of()
            (1, 2)
            (3, 4);

        $this->assertTrue($map->matches(static fn($key, $value) => $value % 2 === 0));
        $this->assertFalse($map->matches(static fn($key, $value) => $key % 2 === 0));
    }

    public function testAny()
    {
        $map = Map::of()
            (1, 2)
            (3, 4);

        $this->assertTrue($map->any(static fn($key, $value) => $value === 4));
        $this->assertTrue($map->any(static fn($key, $value) => $key === 1));
        $this->assertFalse($map->any(static fn($key, $value) => $key === 0));
        $this->assertFalse($map->any(static fn($key, $value) => $value === 1));
    }
}
