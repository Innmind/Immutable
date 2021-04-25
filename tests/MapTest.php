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
    Exception\LogicException,
    Exception\ElementNotFound,
    Exception\CannotGroupEmptyStructure,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testInterface()
    {
        $m = Map::of('int', 'float');

        $this->assertInstanceOf(\Countable::class, $m);
        $this->assertSame('int', $m->keyType());
        $this->assertSame('float', $m->valueType());
    }

    public function testOf()
    {
        $map = Map::of('int', 'float')
            (1, 1.1)
            (2, 2.1);

        $this->assertTrue(
            $map->equals(
                Map::of('int', 'float')
                    ->put(1, 1.1)
                    ->put(2, 2.1)
            )
        );
    }

    public function testEmptyOf()
    {
        $this->assertTrue(Map::of('int', 'int')->equals(Map::of('int', 'int')));
    }

    public function testPut()
    {
        $m = Map::of('int', 'int');

        $this->assertSame(0, $m->size());
        $m2 = $m->put(42, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = Map::of('int', 'int');
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
        $map = Map::of('int', 'int')
            (1, 2)
            (3, 4);
        $expected = Map::of('int', 'int')
            ->put(1, 2)
            ->put(3, 4);

        $this->assertTrue($map->equals($expected));
    }

    public function testGet()
    {
        $m = Map::of('int', 'int');
        $m = $m->put(23, 24);

        $this->assertSame(24, $m->get(23));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFound::class);

        Map::of('int', 'int')->get(24);
    }

    public function testContains()
    {
        $m = Map::of('int', 'int');
        $m = $m->put(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testClear()
    {
        $m = Map::of('int', 'float');
        $m = $m->put(24, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
        $this->assertSame('int', $m2->keyType());
        $this->assertSame('float', $m2->valueType());
    }

    public function testEquals()
    {
        $m = Map::of('int', 'int')->put(24, 42);
        $m2 = Map::of('int', 'int')->put(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals($m2->put(65, 66)));
        $this->assertFalse($m->equals($m2->put(24, 24)));
        $this->assertFalse(
            Map::of('string', 'string')
                ->put('foo_res', 'res')
                ->put('foo_bar_res', 'res')
                ->equals(
                    Map::of('string', 'string')
                        ->put('foo_res', 'res')
                        ->put('bar_res', 'res')
                )
        );

        $m = Map::of('int', 'int')
            ->put(24, 42)
            ->put(42, 24);
        $m2 = Map::of('int', 'int')
            ->put(42, 24)
            ->put(24, 42);

        $this->assertTrue($m->equals($m2));

        $this->assertTrue(Map::of('int', 'int')->equals(Map::of('int', 'int')));
    }

    public function testFilter()
    {
        $m = Map::of('int', 'int')
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->filter(static function(int $key, int $value) {
            return ($key + $value) % 3 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(4));
        $this->assertFalse($m2->contains(0));
        $this->assertFalse($m2->contains(2));
    }

    public function testForeach()
    {
        $m = Map::of('int', 'int')
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

    public function testThrowWhenGroupingAnEmptyMap()
    {
        $this->expectException(CannotGroupEmptyStructure::class);

        Map::of('int', 'int')->groupBy(static function() {});
    }

    public function testGroup()
    {
        $m = Map::of('int', 'int')
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->group('int', static function(int $key, int $value) {
            return ($key + $value) % 3;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame('int', $m2->keyType());
        $this->assertSame(Map::class, $m2->valueType());
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(2));
        $this->assertSame(2, $m2->get(0)->size());
        $this->assertSame(1, $m2->get(1)->size());
        $this->assertSame(1, $m2->get(2)->size());
        $this->assertSame('int', $m2->get(0)->keyType());
        $this->assertSame('int', $m2->get(0)->valueType());
        $this->assertSame('int', $m2->get(1)->keyType());
        $this->assertSame('int', $m2->get(1)->valueType());
        $this->assertSame('int', $m2->get(2)->keyType());
        $this->assertSame('int', $m2->get(2)->valueType());
        $this->assertSame(1, $m2->get(1)->get(0));
        $this->assertSame(2, $m2->get(0)->get(1));
        $this->assertSame(3, $m2->get(2)->get(2));
        $this->assertSame(5, $m2->get(0)->get(4));

        $groups = Map::of('int', 'int')->group('string', static fn() => '');

        $this->assertTrue($groups->isOfType('string', Map::class));
        $this->assertTrue($groups->empty());
    }

    public function testGroupBy()
    {
        $m = Map::of('int', 'int')
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->groupBy(static function(int $key, int $value) {
            return ($key + $value) % 3;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame('int', $m2->keyType());
        $this->assertSame(Map::class, $m2->valueType());
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(2));
        $this->assertSame(2, $m2->get(0)->size());
        $this->assertSame(1, $m2->get(1)->size());
        $this->assertSame(1, $m2->get(2)->size());
        $this->assertSame('int', $m2->get(0)->keyType());
        $this->assertSame('int', $m2->get(0)->valueType());
        $this->assertSame('int', $m2->get(1)->keyType());
        $this->assertSame('int', $m2->get(1)->valueType());
        $this->assertSame('int', $m2->get(2)->keyType());
        $this->assertSame('int', $m2->get(2)->valueType());
        $this->assertSame(1, $m2->get(1)->get(0));
        $this->assertSame(2, $m2->get(0)->get(1));
        $this->assertSame(3, $m2->get(2)->get(2));
        $this->assertSame(5, $m2->get(0)->get(4));
    }

    public function testKeys()
    {
        $m = Map::of('int', 'int')
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame('int', $k->type());
        $this->assertSame([0, 1, 2, 4], unwrap($k));
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = Map::of('int', 'int')
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5)
            ->put(5, 5);

        $v = $m->values();
        $this->assertInstanceOf(Sequence::class, $v);
        $this->assertSame('int', $v->type());
        $this->assertSame([1, 2, 3, 5, 5], unwrap($v));
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = Map::of('int', 'int')
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->map(static function(int $key, int $value) {
            if ($key % 2 === 0) {
                return new Pair($key + 10, $value);
            }

            return $value**2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame([0, 1, 2, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 5], unwrap($m->values()));
        $this->assertSame([10, 1, 12, 14], unwrap($m2->keys()));
        $this->assertSame([1, 4, 3, 5], unwrap($m2->values()));
    }

    public function testRemove()
    {
        $m = Map::of('int', 'int')
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
        $m = Map::of(\stdClass::class, 'int')
            ->put($s = new \stdClass, 24)
            ->put($s2 = new \stdClass, 42);
        $m2 = Map::of(\stdClass::class, 'int')
            ->put($s3 = new \stdClass, 24)
            ->put($s2, 66)
            ->put($s4 = new \stdClass, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(Map::class, $m3);
        $this->assertSame($m->keyType(), $m3->keyType());
        $this->assertSame($m->valueType(), $m3->valueType());
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
        $m = Map::of('int', 'int')
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
        $this->assertSame('bool', $p->keyType());
        $this->assertSame(Map::class, $p->valueType());
        $this->assertSame(
            [true, false],
            unwrap($p->keys())
        );
        $this->assertSame('int', $p->get(true)->keyType());
        $this->assertSame('int', $p->get(true)->valueType());
        $this->assertSame('int', $p->get(false)->keyType());
        $this->assertSame('int', $p->get(false)->valueType());
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
        $m = Map::of('int', 'int')
            ->put(4, 4);

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

    public function testToSetOf()
    {
        $map = Map::of('int', 'int')
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
        $map = Map::of('int', 'int')
            (1, 2)
            (3, 4);
        $map = $map->toMapOf('string', 'int', static fn($i, $j) => yield (string) $j => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(2, $map);
        $this->assertSame(1, $map->get('2'));
        $this->assertSame(3, $map->get('4'));

        $this->assertTrue(
            Map::of('object', 'int')
                ->put(new \stdClass, 1)
                ->toMapOf('stdClass', 'int')
                ->isOfType('stdClass', 'int')
        );
    }

    public function testMatches()
    {
        $map = Map::of('int', 'int')
            (1, 2)
            (3, 4);

        $this->assertTrue($map->matches(static fn($key, $value) => $value % 2 === 0));
        $this->assertFalse($map->matches(static fn($key, $value) => $key % 2 === 0));
    }

    public function testAny()
    {
        $map = Map::of('int', 'int')
            (1, 2)
            (3, 4);

        $this->assertTrue($map->any(static fn($key, $value) => $value === 4));
        $this->assertTrue($map->any(static fn($key, $value) => $key === 1));
        $this->assertFalse($map->any(static fn($key, $value) => $key === 0));
        $this->assertFalse($map->any(static fn($key, $value) => $value === 1));
    }
}
