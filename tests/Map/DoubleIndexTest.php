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
    Exception\LogicException,
    Exception\ElementNotFound,
    Exception\CannotGroupEmptyStructure,
};
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;

class DoubleIndexTest extends TestCase
{
    public function testInterface()
    {
        $m = new DoubleIndex('int', 'float');

        $this->assertInstanceOf(Map\Implementation::class, $m);
        $this->assertInstanceOf(\Countable::class, $m);
        $this->assertSame('int', $m->keyType());
        $this->assertSame('float', $m->valueType());
    }

    public function testPut()
    {
        $m = new DoubleIndex('int', 'int');

        $this->assertSame(0, $m->size());
        $m2 = $m->put(42, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new DoubleIndex('int', 'int');
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

    public function testThrowWhenKeyDoesntMatchType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

        $m = new DoubleIndex('int', 'int');
        $m->put('24', 42);
    }

    public function testThrowWhenValueDoesntMatchType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type int, float given');

        $m = new DoubleIndex('int', 'int');
        $m->put(24, 42.0);
    }

    public function testGet()
    {
        $m = new DoubleIndex('int', 'int');
        $m = $m->put(23, 24);

        $this->assertSame(24, $m->get(23));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFound::class);

        (new DoubleIndex('int', 'int'))->get(24);
    }

    public function testContains()
    {
        $m = new DoubleIndex('int', 'int');
        $m = $m->put(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testClear()
    {
        $m = new DoubleIndex('int', 'float');
        $m = $m->put(24, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
        $this->assertSame('int', $m2->keyType());
        $this->assertSame('float', $m2->valueType());
    }

    public function testEquals()
    {
        $m = (new DoubleIndex('int', 'int'))->put(24, 42);
        $m2 = (new DoubleIndex('int', 'int'))->put(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals($m2->put(65, 66)));
        $this->assertFalse($m->equals($m2->put(24, 24)));
        $this->assertFalse(
            (new DoubleIndex('string', 'string'))
                ->put('foo_res', 'res')
                ->put('foo_bar_res', 'res')
                ->equals(
                    (new DoubleIndex('string', 'string'))
                        ->put('foo_res', 'res')
                        ->put('bar_res', 'res')
                )
        );

        $m = (new DoubleIndex('int', 'int'))
            ->put(24, 42)
            ->put(42, 24);
        $m2 = (new DoubleIndex('int', 'int'))
            ->put(42, 24)
            ->put(24, 42);

        $this->assertTrue($m->equals($m2));

        $this->assertTrue((new DoubleIndex('int', 'int'))->equals(new DoubleIndex('int', 'int')));
    }

    public function testFilter()
    {
        $m = (new DoubleIndex('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->filter(function(int $key, int $value) {
            return ($key + $value) % 3 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(DoubleIndex::class, $m2);
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
        $m = (new DoubleIndex('int', 'int'))
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

        (new DoubleIndex('int', 'int'))->groupBy(function() {});
    }

    public function testGroupBy()
    {
        $m = (new DoubleIndex('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->groupBy(function(int $key, int $value) {
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
        $m = (new DoubleIndex('int', 'int'))
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
        $m = (new DoubleIndex('int', 'int'))
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
        $m = (new DoubleIndex('int', 'int'))
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
        $this->assertInstanceOf(DoubleIndex::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame([0, 1, 2, 4], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 5], unwrap($m->values()));
        $this->assertSame([10, 1, 12, 14], unwrap($m2->keys()));
        $this->assertSame([1, 4, 3, 5], unwrap($m2->values()));
    }

    public function testThrowWhenTryingToModifyValueTypeInTheMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type int, string given');

        (new DoubleIndex('int', 'int'))
            ->put(1, 2)
            ->map(function(int $key, int $value) {
                return (string) $value;
            });
    }

    public function testThrowWhenTryingToModifyKeyTypeInTheMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, string given');

        (new DoubleIndex('int', 'int'))
            ->put(1, 2)
            ->map(function(int $key, int $value) {
                return new Pair((string) $key, $value);
            });
    }

    public function testRemove()
    {
        $m = (new DoubleIndex('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4)
            ->put(4, 5);

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
        $m = (new DoubleIndex(\stdClass::class, 'int'))
            ->put($s = new \stdClass, 24)
            ->put($s2 = new \stdClass, 42);
        $m2 = (new DoubleIndex(\stdClass::class, 'int'))
            ->put($s3 = new \stdClass, 24)
            ->put($s2, 66)
            ->put($s4 = new \stdClass, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(DoubleIndex::class, $m3);
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
        $m = (new DoubleIndex('int', 'int'))
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
        $this->assertSame('bool', $p->keyType());
        $this->assertSame(Map::class, $p->valueType());
        $this->assertSame(
            [true, false],
            unwrap($p->keys()),
        );
        $this->assertSame('int', $p->get(true)->keyType());
        $this->assertSame('int', $p->get(true)->valueType());
        $this->assertSame('int', $p->get(false)->keyType());
        $this->assertSame('int', $p->get(false)->valueType());
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
        $m = (new DoubleIndex('int', 'int'))
            ->put(4, 4);

        $v = $m->reduce(
            42,
            function (float $carry, int $key, int $value): float {
                return $carry / ($key * $value);
            }
        );

        $this->assertSame(2.625, $v);
        $this->assertSame([4], unwrap($m->keys()));
        $this->assertSame([4], unwrap($m->values()));
    }

    public function testEmpty()
    {
        $this->assertTrue((new DoubleIndex('int', 'int'))->empty());
        $this->assertFalse((new DoubleIndex('int', 'int'))->put(1, 2)->empty());
    }

    public function testToSequenceOf()
    {
        $map = (new DoubleIndex('int', 'int'))
            ->put(1, 2)
            ->put(3, 4);
        $sequence = $map->toSequenceOf('int', function($k, $v) {
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
        $map = (new DoubleIndex('int', 'int'))
            ->put(1, 2)
            ->put(3, 4);
        $set = $map->toSetOf('int', function($k, $v) {
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
        $map = (new DoubleIndex('int', 'int'))
            ->put(1, 2)
            ->put(3, 4);
        $map = $map->toMapOf('string', 'int', fn($i, $j) => yield (string) $j => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(2, $map);
        $this->assertSame(1, $map->get('2'));
        $this->assertSame(3, $map->get('4'));
    }
}
