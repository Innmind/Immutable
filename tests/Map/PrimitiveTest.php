<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map\Primitive,
    MapInterface,
    SizeableInterface,
    Pair,
    Str,
    SetInterface,
    StreamInterface,
    Exception\LogicException,
    Exception\InvalidArgumentException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};
use PHPUnit\Framework\TestCase;

class PrimitiveTest extends TestCase
{
    public function testInterface()
    {
        $m = new Primitive('int', 'float');

        $this->assertInstanceOf(MapInterface::class, $m);
        $this->assertInstanceOf(SizeableInterface::class, $m);
        $this->assertInstanceOf(\Countable::class, $m);
        $this->assertInstanceOf(\Iterator::class, $m);
        $this->assertInstanceOf(\ArrayAccess::class, $m);
        $this->assertInstanceOf(Str::class, $m->keyType());
        $this->assertInstanceOf(Str::class, $m->valueType());
        $this->assertSame('int', (string) $m->keyType());
        $this->assertSame('float', (string) $m->valueType());
    }

    public function testAcceptKeyTypes()
    {
        foreach (['int', 'integer', 'string'] as $type) {
            $this->assertSame($type, (string) (new Primitive($type, 'stdClass'))->keyType());
        }
    }

    public function testThrowWhenInvalidKeyType()
    {
        $this->expectException(LogicException::class);

        new Primitive('float', 'stdClass');
    }

    public function testPut()
    {
        $m = new Primitive('int', 'int');

        $this->assertSame(0, $m->size());
        $m2 = $m->put(42, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new Primitive('int', 'int');
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

    public function testThrowWhenInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Primitive('int', 'int'))->put(42, 42.0);
    }

    public function testIterator()
    {
        $m = new Primitive('int', 'int');
        $m = $m
            ->put(23, 24)
            ->put(41, 42)
            ->put(65, 66);

        $this->assertSame(24, $m->current());
        $this->assertSame(23, $m->key());
        $this->assertTrue($m->valid());
        $this->assertSame(null, $m->next());
        $this->assertSame(42, $m->current());
        $this->assertSame(41, $m->key());
        $this->assertTrue($m->valid());
        $m->next();
        $m->next();
        $this->assertFalse($m->valid());
        $this->assertSame(null, $m->rewind());
        $this->assertSame(24, $m->current());
        $this->assertSame(23, $m->key());
    }

    public function testArrayAccess()
    {
        $m = new Primitive('int', 'stdClass');
        $m = $m->put(24, $v = new \stdClass);

        $this->assertTrue(isset($m[24]));
        $this->assertSame($v, $m[24]);
    }

    public function testThrowWhenInjectingData()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You can\'t modify a map');

        $m = new Primitive('int', 'int');
        $m[24] = 42;
    }

    public function testThrowWhenDeletingData()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You can\'t modify a map');

        $m = new Primitive('int', 'int');
        $m = $m->put(24, 42);

        unset($m[24]);
    }

    public function testThrowWhenUnknownOffset()
    {
        $this->expectException(ElementNotFoundException::class);

        $m = new Primitive('int', 'int');
        $m[24];
    }

    public function testThrowWhenKeyDoesntMatchType()
    {
        $this->expectException(InvalidArgumentException::class);

        $m = new Primitive('int', 'int');
        $m->put('24', 42);
    }

    public function testThrowWhenValueDoesntMatchType()
    {
        $this->expectException(InvalidArgumentException::class);

        $m = new Primitive('int', 'int');
        $m->put(24, 42.0);
    }

    public function testGet()
    {
        $m = new Primitive('int', 'int');
        $m = $m->put(23, 24);

        $this->assertSame(24, $m->get(23));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFoundException::class);

        (new Primitive('int', 'int'))->get(24);
    }

    public function testContains()
    {
        $m = new Primitive('int', 'int');
        $m = $m->put(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testClear()
    {
        $m = new Primitive('int', 'float');
        $m = $m->put(24, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
        $this->assertSame('int', (string) $m2->keyType());
        $this->assertSame('float', (string) $m2->valueType());
    }

    public function testEquals()
    {
        $m = (new Primitive('int', 'int'))->put(24, 42);
        $m2 = (new Primitive('int', 'int'))->put(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals($m2->put(65, 66)));
        $this->assertFalse($m->equals($m2->put(24, 24)));
        $this->assertFalse(
            (new Primitive('string', 'string'))
                ->put('foo_res', 'res')
                ->put('foo_bar_res', 'res')
                ->equals(
                    (new Primitive('string', 'string'))
                        ->put('foo_res', 'res')
                        ->put('bar_res', 'res')
                )
        );

        $m = (new Primitive('int', 'int'))
            ->put(24, 42)
            ->put(42, 24);
        $m2 = (new Primitive('int', 'int'))
            ->put(42, 24)
            ->put(24, 42);

        $this->assertTrue($m->equals($m2));

        $this->assertTrue((new Primitive('int', 'int'))->equals(new Primitive('int', 'int')));
    }

    public function testFilter()
    {
        $m = (new Primitive('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->filter(function(int $key, int $value) {
            return ($key + $value) % 3 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
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
        $m = (new Primitive('int', 'int'))
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

        (new Primitive('int', 'int'))->groupBy(function() {});
    }

    public function testGroupBy()
    {
        $m = (new Primitive('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->groupBy(function(int $key, int $value) {
            return ($key + $value) % 3;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(MapInterface::class, $m2);
        $this->assertSame('int', (string) $m2->keyType());
        $this->assertSame(MapInterface::class, (string) $m2->valueType());
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
        $m = (new Primitive('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $k = $m->keys();
        $this->assertInstanceOf(SetInterface::class, $k);
        $this->assertSame('int', (string) $k->type());
        $this->assertSame([0, 1, 2, 4], $k->toPrimitive());
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = (new Primitive('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5)
            ->put(5, 5);

        $v = $m->values();
        $this->assertInstanceOf(StreamInterface::class, $v);
        $this->assertSame('int', (string) $v->type());
        $this->assertSame([1, 2, 3, 5, 5], $v->toPrimitive());
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = (new Primitive('int', 'int'))
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
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame([0, 1, 2, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 5], $m->values()->toPrimitive());
        $this->assertSame([10, 1, 12, 14], $m2->keys()->toPrimitive());
        $this->assertSame([1, 4, 3, 5], $m2->values()->toPrimitive());
    }

    public function testThrowWhenTryingToModifyValueTypeInTheMap()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Primitive('int', 'int'))
            ->put(1, 2)
            ->map(function(int $key, int $value) {
                return (string) $value;
            });
    }

    public function testThrowWhenTryingToModifyKeyTypeInTheMap()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Primitive('int', 'int'))
            ->put(1, 2)
            ->map(function(int $key, int $value) {
                return new Pair((string) $key, $value);
            });
    }

    public function testJoin()
    {
        $m = (new Primitive('int', 'int'))
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
        $m = (new Primitive('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4)
            ->put(4, 5);

        $m2 = $m->remove(12);
        $this->assertSame($m, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());

        $m2 = $m->remove(3);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());
        $this->assertSame([0, 1, 2, 4], $m2->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 5], $m2->values()->toPrimitive());

        $m2 = $m->remove(4);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());
        $this->assertSame([0, 1, 2, 3], $m2->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4], $m2->values()->toPrimitive());

        $m2 = $m->remove(0);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Primitive::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());
        $this->assertSame([1, 2, 3, 4], $m2->keys()->toPrimitive());
        $this->assertSame([2, 3, 4, 5], $m2->values()->toPrimitive());
    }

    public function testMerge()
    {
        $m = (new Primitive('int', 'int'))
            ->put($s = 90, 24)
            ->put($s2 = 91, 42);
        $m2 = (new Primitive('int', 'int'))
            ->put($s3 = 92, 24)
            ->put($s2, 66)
            ->put($s4 = 93, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(Primitive::class, $m3);
        $this->assertSame($m->keyType(), $m3->keyType());
        $this->assertSame($m->valueType(), $m3->valueType());
        $this->assertSame(4, $m3->size());
        $this->assertSame([$s, $s2], $m->keys()->toPrimitive());
        $this->assertSame([24, 42], $m->values()->toPrimitive());
        $this->assertSame([$s3, $s2, $s4], $m2->keys()->toPrimitive());
        $this->assertSame([24, 66, 42], $m2->values()->toPrimitive());
        $this->assertSame([$s, $s2, $s3, $s4], $m3->keys()->toPrimitive());
        $this->assertSame([24, 66, 24, 42], $m3->values()->toPrimitive());
        $this->assertFalse($m3->equals($m2->merge($m)));
    }

    public function testThrowWhenMergingSetsOfDifferentType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The 2 maps does not reference the same types');

        (new Primitive('int', 'int'))->merge(new Primitive('string', 'int'));
    }

    public function testPartition()
    {
        $m = (new Primitive('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(3, 4)
            ->put(4, 5);

        $p = $m->partition(function(int $i, int $v) {
            return ($i + $v) % 3 === 0;
        });

        $this->assertInstanceOf(MapInterface::class, $p);
        $this->assertNotSame($p, $m);
        $this->assertSame('bool', (string) $p->keyType());
        $this->assertSame(MapInterface::class, (string) $p->valueType());
        $this->assertSame(
            [true, false],
            $p->keys()->toPrimitive()
        );
        $this->assertSame('int', (string) $p->get(true)->keyType());
        $this->assertSame('int', (string) $p->get(true)->valueType());
        $this->assertSame('int', (string) $p->get(false)->keyType());
        $this->assertSame('int', (string) $p->get(false)->valueType());
        $this->assertSame(
            [1, 4],
            $p->get(true)->keys()->toPrimitive()
        );
        $this->assertSame(
            [2, 5],
            $p->get(true)->values()->toPrimitive()
        );
        $this->assertSame(
            [0, 2, 3],
            $p->get(false)->keys()->toPrimitive()
        );
        $this->assertSame(
            [1, 3, 4],
            $p->get(false)->values()->toPrimitive()
        );
    }

    public function testReduce()
    {
        $m = (new Primitive('int', 'int'))
            ->put(4, 4);

        $v = $m->reduce(
            42,
            function (float $carry, int $key, int $value): float {
                return $carry / ($key * $value);
            }
        );

        $this->assertSame(2.625, $v);
        $this->assertSame([4], $m->keys()->toPrimitive());
        $this->assertSame([4], $m->values()->toPrimitive());
    }

    public function testEmpty()
    {
        $this->assertTrue((new Primitive('int', 'int'))->empty());
        $this->assertFalse((new Primitive('int', 'int'))->put(1, 2)->empty());
    }

    public function testWorkAroundPhpImplicitCast()
    {
        $map = (new Primitive('string', 'string'))->put('1', 'foo');

        $this->assertTrue($map->contains('1'));
        $this->assertSame('1', $map->key());
        $this->assertSame('foo', $map->get('1'));
        $this->assertSame(['1'], $map->keys()->toPrimitive());
        $this->assertTrue($map->valid());
        $map->next();
        $this->assertFalse($map->valid());
    }
}
