<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map\ObjectKeys,
    Map\DoubleIndex,
    Map\Implementation,
    Map,
    Set,
    Sequence,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class ObjectKeysTest extends TestCase
{
    public function testInterface()
    {
        $m = new ObjectKeys;

        $this->assertInstanceOf(Implementation::class, $m);
        $this->assertInstanceOf(\Countable::class, $m);
    }

    public function testPut()
    {
        $m = new ObjectKeys;

        $this->assertSame(0, $m->size());
        $m2 = ($m)(new \stdClass, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new ObjectKeys;
        $m = $m
            ($a = new \stdClass, 24)
            ($b = new \stdClass, 42)
            ($c = new \stdClass, 66)
            ($d = new \stdClass, 90)
            ($c, 1);

        $this->assertSame(24, $this->get($m, $a));
        $this->assertSame(42, $this->get($m, $b));
        $this->assertSame(1, $this->get($m, $c));
        $this->assertSame(90, $this->get($m, $d));
        $this->assertSame(4, $m->size());
    }

    public function testGet()
    {
        $m = new ObjectKeys;
        $m = ($m)($a = new \stdClass, 24);

        $this->assertSame(24, $this->get($m, $a));
    }

    public function testReturnNothingWhenGettingUnknownKey()
    {
        $this->assertNull($this->get(new ObjectKeys, new \stdClass));
    }

    public function testContains()
    {
        $m = new ObjectKeys;
        $m = ($m)($a = new \stdClass, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains($a));
    }

    public function testClear()
    {
        $m = new ObjectKeys;
        $m = ($m)(new \stdClass, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
    }

    public function testEquals()
    {
        $m = (new ObjectKeys)($a = new \stdClass, 42);
        $m2 = (new ObjectKeys)($a, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals(($m2)(new \stdClass, 66)));
        $this->assertFalse($m->equals(($m2)($a, 24)));
    }

    public function testFilter()
    {
        $m = (new ObjectKeys)
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4);

        $m2 = $m->filter(static function(\stdClass $key, int $value) {
            return $value % 2 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertTrue($m2->contains($b));
        $this->assertTrue($m2->contains($d));
        $this->assertFalse($m2->contains($a));
        $this->assertFalse($m2->contains($c));
    }

    public function testForeach()
    {
        $m = (new ObjectKeys)
            (new \stdClass, 1)
            (new \stdClass, 2)
            (new \stdClass, 3)
            (new \stdClass, 4);
        $count = 0;

        $m->foreach(function(\stdClass $key, int $value) use (&$count) {
            ++$count;
            $this->assertSame($count, $value);
        });
        $this->assertSame(4, $count);
    }

    public function testGroupEmptyMap()
    {
        $this->assertTrue(
            (new ObjectKeys)
                ->groupBy(static function() {})
                ->equals(Map::of()),
        );
    }

    public function testGroupBy()
    {
        $m = (new ObjectKeys)
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4);

        $m2 = $m->groupBy(static function(\stdClass $key, int $value) {
            return $value % 2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertSame(2, $this->get($m2, 0)->size());
        $this->assertSame(2, $this->get($m2, 1)->size());
        $this->assertSame(1, $this->get($this->get($m2, 1), $a));
        $this->assertSame(2, $this->get($this->get($m2, 0), $b));
        $this->assertSame(3, $this->get($this->get($m2, 1), $c));
        $this->assertSame(4, $this->get($this->get($m2, 0), $d));
    }
    public function testKeys()
    {
        $m = (new ObjectKeys)
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame([$a, $b, $c, $d], $k->toList());
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = (new ObjectKeys)
            (new \stdClass, 1)
            (new \stdClass, 2)
            (new \stdClass, 3)
            (new \stdClass, 5)
            (new \stdClass, 5);

        $v = $m->values();
        $this->assertInstanceOf(Sequence::class, $v);
        $this->assertSame([1, 2, 3, 5, 5], $v->toList());
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = (new ObjectKeys)
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4);

        $m2 = $m->map(static function(\stdClass $key, int $value) {
            return $value**2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4], $m->values()->toList());
        $this->assertSame([$a, $b, $c, $d], $m2->keys()->toList());
        $this->assertSame([1, 4, 9, 16], $m2->values()->toList());
    }

    public function testRemove()
    {
        $m = (new ObjectKeys)
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4)
            ($e = new \stdClass, 5);

        $m2 = $m->remove(new \stdClass);
        $this->assertSame($m, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());

        $m2 = $m->remove($d);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());
        $this->assertSame([$a, $b, $c, $e], $m2->keys()->toList());
        $this->assertSame([1, 2, 3, 5], $m2->values()->toList());

        $m2 = $m->remove($e);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());
        $this->assertSame([$a, $b, $c, $d], $m2->keys()->toList());
        $this->assertSame([1, 2, 3, 4], $m2->values()->toList());

        $m2 = $m->remove($a);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toList());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toList());
        $this->assertSame([$b, $c, $d, $e], $m2->keys()->toList());
        $this->assertSame([2, 3, 4, 5], $m2->values()->toList());
    }

    public function testMerge()
    {
        $m = (new ObjectKeys)
            ($s = new \stdClass, 24)
            ($s2 = new \stdClass, 42);
        $m2 = (new ObjectKeys)
            ($s3 = new \stdClass, 24)
            ($s2, 66)
            ($s4 = new \stdClass, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(ObjectKeys::class, $m3);
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
        $m = (new ObjectKeys)
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4)
            ($e = new \stdClass, 5);

        $p = $m->partition(static function(\stdClass $i, int $v) {
            return $v % 2 === 0;
        });

        $this->assertInstanceOf(Map::class, $p);
        $this->assertNotSame($p, $m);
        $this->assertSame(
            [true, false],
            $p->keys()->toList(),
        );
        $this->assertSame(
            [$b, $d],
            $this->get($p, true)->keys()->toList(),
        );
        $this->assertSame(
            [2, 4],
            $this->get($p, true)->values()->toList(),
        );
        $this->assertSame(
            [$a, $c, $e],
            $this->get($p, false)->keys()->toList(),
        );
        $this->assertSame(
            [1, 3, 5],
            $this->get($p, false)->values()->toList(),
        );
    }

    public function testReduce()
    {
        $m = (new ObjectKeys)($a = new \stdClass, 4);

        $v = $m->reduce(
            42,
            static function(float $carry, \stdClass $key, int $value): float {
                return $carry / $value;
            },
        );

        $this->assertSame(10.5, $v);
        $this->assertSame([$a], $m->keys()->toList());
        $this->assertSame([4], $m->values()->toList());
    }

    public function testEmpty()
    {
        $this->assertTrue((new ObjectKeys)->empty());
        $this->assertFalse((new ObjectKeys)(new \stdClass, 1)->empty());
    }

    public function testSwitchImplementationWhenAddingNonKeyObject()
    {
        $map = (new ObjectKeys)(1, 2);

        $this->assertInstanceOf(DoubleIndex::class, $map);
        $this->assertCount(1, $map);
    }

    public function testFind()
    {
        $a = new \stdClass;
        $b = new \stdClass;
        $c = new \stdClass;
        $map = (new ObjectKeys)
            ($a, 2)
            ($b, 4)
            ($c, 6);

        $this->assertSame(
            4,
            $map
                ->find(static fn($k) => $k === $b)
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
