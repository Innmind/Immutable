<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Map,
    SizeableInterface,
    Pair,
    Str,
    Symbol,
    Set,
    StreamInterface,
    Exception\LogicException,
    Exception\InvalidArgumentException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testInterface()
    {
        $m = new Map('int', 'float');

        $this->assertInstanceOf(\Countable::class, $m);
        $this->assertInstanceOf(Str::class, $m->keyType());
        $this->assertInstanceOf(Str::class, $m->valueType());
        $this->assertSame('int', (string) $m->keyType());
        $this->assertSame('float', (string) $m->valueType());
    }

    public function testOf()
    {
        $map = Map::of('int', 'float', [1, 2], [1.1, 2.1]);

        $this->assertTrue(
            $map->equals(
                (new Map('int', 'float'))
                    ->put(1, 1.1)
                    ->put(2, 2.1)
            )
        );
    }

    public function testEmptyOf()
    {
        $this->assertTrue(Map::of('int', 'int')->equals(new Map('int', 'int')));
    }

    public function testThrowWhenDifferentSizes()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Different sizes of keys and values');

        Map::of('int', 'float', [], [1.1]);
    }

    public function testPut()
    {
        $m = new Map('int', 'int');

        $this->assertSame(0, $m->size());
        $m2 = $m->put(42, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new Map('int', 'int');
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

    public function testThrowWhenInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Map('int', 'int'))->put(42, 42.0);
    }

    public function testThrowWhenKeyDoesntMatchType()
    {
        $this->expectException(InvalidArgumentException::class);

        $m = new Map('int', 'int');
        $m->put('24', 42);
    }

    public function testThrowWhenValueDoesntMatchType()
    {
        $this->expectException(InvalidArgumentException::class);

        $m = new Map('int', 'int');
        $m->put(24, 42.0);
    }

    public function testGet()
    {
        $m = new Map('int', 'int');
        $m = $m->put(23, 24);

        $this->assertSame(24, $m->get(23));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFoundException::class);

        (new Map('int', 'int'))->get(24);
    }

    public function testContains()
    {
        $m = new Map('int', 'int');
        $m = $m->put(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testClear()
    {
        $m = new Map('int', 'float');
        $m = $m->put(24, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
        $this->assertSame('int', (string) $m2->keyType());
        $this->assertSame('float', (string) $m2->valueType());
    }

    public function testEquals()
    {
        $m = (new Map('int', 'int'))->put(24, 42);
        $m2 = (new Map('int', 'int'))->put(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals($m2->put(65, 66)));
        $this->assertFalse($m->equals($m2->put(24, 24)));
        $this->assertFalse(
            (new Map('string', 'string'))
                ->put('foo_res', 'res')
                ->put('foo_bar_res', 'res')
                ->equals(
                    (new Map('string', 'string'))
                        ->put('foo_res', 'res')
                        ->put('bar_res', 'res')
                )
        );

        $m = (new Map('int', 'int'))
            ->put(24, 42)
            ->put(42, 24);
        $m2 = (new Map('int', 'int'))
            ->put(42, 24)
            ->put(24, 42);

        $this->assertTrue($m->equals($m2));

        $this->assertTrue((new Map('int', 'int'))->equals(new Map('int', 'int')));
    }

    public function testFilter()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->filter(function(int $key, int $value) {
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
        $m = (new Map('int', 'int'))
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
        $this->expectException(GroupEmptyMapException::class);

        (new Map('int', 'int'))->groupBy(function() {});
    }

    public function testGroupBy()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->groupBy(function(int $key, int $value) {
            return ($key + $value) % 3;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame('int', (string) $m2->keyType());
        $this->assertSame(Map::class, (string) $m2->valueType());
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(2));
        $this->assertSame(2, $m2->get(0)->size());
        $this->assertSame(1, $m2->get(1)->size());
        $this->assertSame(1, $m2->get(2)->size());
        $this->assertSame('int', (string) $m2->get(0)->keyType());
        $this->assertSame('int', (string) $m2->get(0)->valueType());
        $this->assertSame('int', (string) $m2->get(1)->keyType());
        $this->assertSame('int', (string) $m2->get(1)->valueType());
        $this->assertSame('int', (string) $m2->get(2)->keyType());
        $this->assertSame('int', (string) $m2->get(2)->valueType());
        $this->assertSame(1, $m2->get(1)->get(0));
        $this->assertSame(2, $m2->get(0)->get(1));
        $this->assertSame(3, $m2->get(2)->get(2));
        $this->assertSame(5, $m2->get(0)->get(4));
    }
    public function testKeys()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame('int', (string) $k->type());
        $this->assertSame([0, 1, 2, 4], $k->toArray());
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5)
            ->put(5, 5);

        $v = $m->values();
        $this->assertInstanceOf(StreamInterface::class, $v);
        $this->assertSame('int', (string) $v->type());
        $this->assertSame([1, 2, 3, 5, 5], $v->toArray());
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->map(function(int $key, int $value) {
            if ($key % 2 === 0) {
                return new Pair($key + 10, $value);
            }

            return $value**2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame([0, 1, 2, 4], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 5], $m->values()->toArray());
        $this->assertSame([10, 1, 12, 14], $m2->keys()->toArray());
        $this->assertSame([1, 4, 3, 5], $m2->values()->toArray());
    }

    public function testTrhowWhenTryingToModifyValueTypeInTheMap()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Map('int', 'int'))
            ->put(1, 2)
            ->map(function(int $key, int $value) {
                return (string) $value;
            });
    }

    public function testTrhowWhenTryingToModifyKeyTypeInTheMap()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Map('int', 'int'))
            ->put(1, 2)
            ->map(function(int $key, int $value) {
                return new Pair((string) $key, $value);
            });
    }

    public function testJoin()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $s = $m->join(', ');
        $this->assertInstanceOf(Str::class, $s);
        $this->assertSame('1, 2, 3, 5', (string) $s);
    }

    public function testRemove()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4)
            ->put(4, 5);

        $m2 = $m->remove(12);
        $this->assertTrue($m->equals($m2));
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());

        $m2 = $m->remove(3);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());
        $this->assertSame([0, 1, 2, 4], $m2->keys()->toArray());
        $this->assertSame([1, 2, 3, 5], $m2->values()->toArray());

        $m2 = $m->remove(4);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());
        $this->assertSame([0, 1, 2, 3], $m2->keys()->toArray());
        $this->assertSame([1, 2, 3, 4], $m2->values()->toArray());

        $m2 = $m->remove(0);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());
        $this->assertSame([1, 2, 3, 4], $m2->keys()->toArray());
        $this->assertSame([2, 3, 4, 5], $m2->values()->toArray());
    }

    public function testMerge()
    {
        $m = (new Map(Symbol::class, 'int'))
            ->put($s = new Symbol('foo'), 24)
            ->put($s2 = new Symbol('foo'), 42);
        $m2 = (new Map(Symbol::class, 'int'))
            ->put($s3 = new Symbol('foo'), 24)
            ->put($s2, 66)
            ->put($s4 = new Symbol('bar'), 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(Map::class, $m3);
        $this->assertSame($m->keyType(), $m3->keyType());
        $this->assertSame($m->valueType(), $m3->valueType());
        $this->assertSame(4, $m3->size());
        $this->assertSame([$s, $s2], $m->keys()->toArray());
        $this->assertSame([24, 42], $m->values()->toArray());
        $this->assertSame([$s3, $s2, $s4], $m2->keys()->toArray());
        $this->assertSame([24, 66, 42], $m2->values()->toArray());
        $this->assertSame([$s, $s2, $s3, $s4], $m3->keys()->toArray());
        $this->assertSame([24, 66, 24, 42], $m3->values()->toArray());
        $this->assertFalse($m3->equals($m2->merge($m)));
    }

    public function testThrowWhenMergingSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 maps does not reference the same types');

        (new Map('int', 'int'))->merge(new Map('float', 'int'));
    }

    public function testPartition()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4)
            ->put(4, 5);

        $p = $m->partition(function(int $i, int $v) {
            return ($i + $v) % 3 === 0;
        });

        $this->assertInstanceOf(Map::class, $p);
        $this->assertNotSame($p, $m);
        $this->assertSame('bool', (string) $p->keyType());
        $this->assertSame(Map::class, (string) $p->valueType());
        $this->assertSame(
            [true, false],
            $p->keys()->toArray()
        );
        $this->assertSame('int', (string) $p->get(true)->keyType());
        $this->assertSame('int', (string) $p->get(true)->valueType());
        $this->assertSame('int', (string) $p->get(false)->keyType());
        $this->assertSame('int', (string) $p->get(false)->valueType());
        $this->assertSame(
            [1, 4],
            $p->get(true)->keys()->toArray()
        );
        $this->assertSame(
            [2, 5],
            $p->get(true)->values()->toArray()
        );
        $this->assertSame(
            [0, 2, 3],
            $p->get(false)->keys()->toArray()
        );
        $this->assertSame(
            [1, 3, 4],
            $p->get(false)->values()->toArray()
        );
    }

    public function testReduce()
    {
        $m = (new Map('int', 'int'))
            ->put(4, 4);

        $v = $m->reduce(
            42,
            function (float $carry, int $key, int $value): float {
                return $carry / ($key * $value);
            }
        );

        $this->assertSame(2.625, $v);
        $this->assertSame([4], $m->keys()->toArray());
        $this->assertSame([4], $m->values()->toArray());
    }
}
