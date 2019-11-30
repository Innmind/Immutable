<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map\ObjectKeys,
    Map\Implementation,
    Map,
    SizeableInterface,
    Pair,
    Str,
    Symbol,
    Set,
    Stream,
    Exception\LogicException,
    Exception\InvalidArgumentException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};
use PHPUnit\Framework\TestCase;

class ObjectKeysTest extends TestCase
{
    public function testInterface()
    {
        $m = new ObjectKeys('stdClass', 'float');

        $this->assertInstanceOf(Map\Implementation::class, $m);
        $this->assertInstanceOf(SizeableInterface::class, $m);
        $this->assertInstanceOf(\Countable::class, $m);
        $this->assertSame('stdClass', $m->keyType());
        $this->assertSame('float', $m->valueType());
    }

    public function testThrowWhenKeyNotAClass()
    {
        $this->expectException(LogicException::class);

        new ObjectKeys('int', 'float');
    }

    public function testPut()
    {
        $m = new ObjectKeys('stdClass', 'int');

        $this->assertSame(0, $m->size());
        $m2 = $m->put(new \stdClass, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new ObjectKeys('stdClass', 'int');
        $m = $m
            ->put($a = new \stdClass, 24)
            ->put($b = new \stdClass, 42)
            ->put($c = new \stdClass, 66)
            ->put($d = new \stdClass, 90)
            ->put($c, 1);

        $this->assertSame(24, $m->get($a));
        $this->assertSame(42, $m->get($b));
        $this->assertSame(1, $m->get($c));
        $this->assertSame(90, $m->get($d));
        $this->assertSame(4, $m->size());
    }

    public function testThrowWhenInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ObjectKeys('stdClass', 'int'))->put(new \stdClass, 42.0);
    }

    public function testThrowWhenKeyDoesntMatchType()
    {
        $this->expectException(InvalidArgumentException::class);

        $m = new ObjectKeys('stdClass', 'int');
        $m->put(new \stdClass, '42');
    }

    public function testThrowWhenValueDoesntMatchType()
    {
        $this->expectException(InvalidArgumentException::class);

        $m = new ObjectKeys('stdClass', 'int');
        $m->put(new \stdClass, 42.0);
    }

    public function testGet()
    {
        $m = new ObjectKeys('stdClass', 'int');
        $m = $m->put($a = new \stdClass, 24);

        $this->assertSame(24, $m->get($a));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFoundException::class);

        (new ObjectKeys('stdClass', 'int'))->get(new \stdClass);
    }

    public function testContains()
    {
        $m = new ObjectKeys('stdClass', 'int');
        $m = $m->put($a = new \stdClass, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains($a));
    }

    public function testClear()
    {
        $m = new ObjectKeys('stdClass', 'float');
        $m = $m->put(new \stdClass, 42.0);

        $m2 = $m->clear();
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame(1, $m->size());
        $this->assertSame(0, $m2->size());
        $this->assertSame('stdClass', $m2->keyType());
        $this->assertSame('float', $m2->valueType());
    }

    public function testEquals()
    {
        $m = (new ObjectKeys('stdClass', 'int'))->put($a = new \stdClass, 42);
        $m2 = (new ObjectKeys('stdClass', 'int'))->put($a, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals($m2->put(new \stdClass, 66)));
        $this->assertFalse($m->equals($m2->put($a, 24)));
    }

    public function testFilter()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put($a = new \stdClass, 1)
            ->put($b = new \stdClass, 2)
            ->put($c = new \stdClass, 3)
            ->put($d = new \stdClass, 4);

        $m2 = $m->filter(function(\stdClass $key, int $value) {
            return $value % 2 === 0;
        });

        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertTrue($m2->contains($b));
        $this->assertTrue($m2->contains($d));
        $this->assertFalse($m2->contains($a));
        $this->assertFalse($m2->contains($c));
    }

    public function testForeach()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put(new \stdClass, 1)
            ->put(new \stdClass, 2)
            ->put(new \stdClass, 3)
            ->put(new \stdClass, 4);
        $count = 0;

        $m->foreach(function(\stdClass $key, int $value) use (&$count) {
            ++$count;
            $this->assertSame($count, $value);
        });
        $this->assertSame(4, $count);
    }

    public function testThrowWhenGroupingAnEmptyMap()
    {
        $this->expectException(GroupEmptyMapException::class);

        (new ObjectKeys('stdClass', 'int'))->groupBy(function() {});
    }

    public function testGroupBy()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put($a = new \stdClass, 1)
            ->put($b = new \stdClass, 2)
            ->put($c = new \stdClass, 3)
            ->put($d = new \stdClass, 4);

        $m2 = $m->groupBy(function(\stdClass $key, int $value) {
            return $value % 2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame('int', $m2->keyType());
        $this->assertSame(Map::class, $m2->valueType());
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertSame(2, $m2->get(0)->size());
        $this->assertSame(2, $m2->get(1)->size());
        $this->assertSame('stdClass', $m2->get(0)->keyType());
        $this->assertSame('int', $m2->get(0)->valueType());
        $this->assertSame('stdClass', $m2->get(1)->keyType());
        $this->assertSame('int', $m2->get(1)->valueType());
        $this->assertSame(1, $m2->get(1)->get($a));
        $this->assertSame(2, $m2->get(0)->get($b));
        $this->assertSame(3, $m2->get(1)->get($c));
        $this->assertSame(4, $m2->get(0)->get($d));
    }
    public function testKeys()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put($a = new \stdClass, 1)
            ->put($b = new \stdClass, 2)
            ->put($c = new \stdClass, 3)
            ->put($d = new \stdClass, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame('stdClass', $k->type());
        $this->assertSame([$a, $b, $c, $d], $k->toArray());
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put(new \stdClass, 1)
            ->put(new \stdClass, 2)
            ->put(new \stdClass, 3)
            ->put(new \stdClass, 5)
            ->put(new \stdClass, 5);

        $v = $m->values();
        $this->assertInstanceOf(Stream::class, $v);
        $this->assertSame('int', $v->type());
        $this->assertSame([1, 2, 3, 5, 5], $v->toArray());
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put($a = new \stdClass, 1)
            ->put($b = new \stdClass, 2)
            ->put($c = new \stdClass, 3)
            ->put($d = new \stdClass, 4);

        $m2 = $m->map(function(\stdClass $key, int $value) {
            if ($value % 2 === 0) {
                return new Pair($key, $value + 10);
            }

            return $value**2;
        });
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame([$a, $b, $c, $d], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4], $m->values()->toArray());
        $this->assertSame([$a, $b, $c, $d], $m2->keys()->toArray());
        $this->assertSame([1, 12, 9, 14], $m2->values()->toArray());
    }

    public function testThrowWhenTryingToModifyValueTypeInTheMap()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ObjectKeys('stdClass', 'int'))
            ->put(new \stdClass, 2)
            ->map(function(\stdClass $key, int $value) {
                return (string) $value;
            });
    }

    public function testThrowWhenTryingToModifyKeyTypeInTheMap()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ObjectKeys('stdClass', 'int'))
            ->put(new \stdClass, 2)
            ->map(function(\stdClass $key, int $value) {
                return new Pair(42, $value);
            });
    }

    public function testJoin()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put(new \stdClass, 1)
            ->put(new \stdClass, 2)
            ->put(new \stdClass, 3)
            ->put(new \stdClass, 5);

        $s = $m->join(', ');
        $this->assertInstanceOf(Str::class, $s);
        $this->assertSame('1, 2, 3, 5', (string) $s);
    }

    public function testRemove()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put($a = new \stdClass, 1)
            ->put($b = new \stdClass, 2)
            ->put($c = new \stdClass, 3)
            ->put($d = new \stdClass, 4)
            ->put($e = new \stdClass, 5);

        $m2 = $m->remove(new \stdClass);
        $this->assertSame($m, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());

        $m2 = $m->remove($d);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());
        $this->assertSame([$a, $b, $c, $e], $m2->keys()->toArray());
        $this->assertSame([1, 2, 3, 5], $m2->values()->toArray());

        $m2 = $m->remove($e);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());
        $this->assertSame([$a, $b, $c, $d], $m2->keys()->toArray());
        $this->assertSame([1, 2, 3, 4], $m2->values()->toArray());

        $m2 = $m->remove($a);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], $m->keys()->toArray());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toArray());
        $this->assertSame([$b, $c, $d, $e], $m2->keys()->toArray());
        $this->assertSame([2, 3, 4, 5], $m2->values()->toArray());
    }

    public function testMerge()
    {
        $m = (new ObjectKeys(Symbol::class, 'int'))
            ->put($s = new Symbol('foo'), 24)
            ->put($s2 = new Symbol('foo'), 42);
        $m2 = (new ObjectKeys(Symbol::class, 'int'))
            ->put($s3 = new Symbol('foo'), 24)
            ->put($s2, 66)
            ->put($s4 = new Symbol('bar'), 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(ObjectKeys::class, $m3);
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

        (new ObjectKeys('stdClass', 'int'))->merge(new ObjectKeys(Symbol::class, 'int'));
    }

    public function testPartition()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put($a = new \stdClass, 1)
            ->put($b = new \stdClass, 2)
            ->put($c = new \stdClass, 3)
            ->put($d = new \stdClass, 4)
            ->put($e = new \stdClass, 5);

        $p = $m->partition(function(\stdClass $i, int $v) {
            return $v % 2 === 0;
        });

        $this->assertInstanceOf(Map::class, $p);
        $this->assertNotSame($p, $m);
        $this->assertSame('bool', $p->keyType());
        $this->assertSame(Map::class, $p->valueType());
        $this->assertSame(
            [true, false],
            $p->keys()->toArray()
        );
        $this->assertSame('stdClass', $p->get(true)->keyType());
        $this->assertSame('int', $p->get(true)->valueType());
        $this->assertSame('stdClass', $p->get(false)->keyType());
        $this->assertSame('int', $p->get(false)->valueType());
        $this->assertSame(
            [$b, $d],
            $p->get(true)->keys()->toArray()
        );
        $this->assertSame(
            [2, 4],
            $p->get(true)->values()->toArray()
        );
        $this->assertSame(
            [$a, $c, $e],
            $p->get(false)->keys()->toArray()
        );
        $this->assertSame(
            [1, 3, 5],
            $p->get(false)->values()->toArray()
        );
    }

    public function testReduce()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ->put($a = new \stdClass, 4);

        $v = $m->reduce(
            42,
            function (float $carry, \stdClass $key, int $value): float {
                return $carry / $value;
            }
        );

        $this->assertSame(10.5, $v);
        $this->assertSame([$a], $m->keys()->toArray());
        $this->assertSame([4], $m->values()->toArray());
    }

    public function testEmpty()
    {
        $this->assertTrue((new ObjectKeys('stdClass', 'int'))->empty());
        $this->assertFalse((new ObjectKeys('stdClass', 'int'))->put(new \stdClass, 1)->empty());
    }

    public function testGenericObjectTypeAllowedAsKey()
    {
        $this->assertSame('object', (new ObjectKeys('object', 'int'))->keyType());
    }
}
