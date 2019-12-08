<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map\ObjectKeys,
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

class ObjectKeysTest extends TestCase
{
    public function testInterface()
    {
        $m = new ObjectKeys('stdClass', 'float');

        $this->assertInstanceOf(Map\Implementation::class, $m);
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
        $m2 = ($m)(new \stdClass, 42);
        $this->assertNotSame($m, $m2);
        $this->assertSame(0, $m->size());
        $this->assertSame(1, $m2->size());

        $m = new ObjectKeys('stdClass', 'int');
        $m = $m
            ($a = new \stdClass, 24)
            ($b = new \stdClass, 42)
            ($c = new \stdClass, 66)
            ($d = new \stdClass, 90)
            ($c, 1);

        $this->assertSame(24, $m->get($a));
        $this->assertSame(42, $m->get($b));
        $this->assertSame(1, $m->get($c));
        $this->assertSame(90, $m->get($d));
        $this->assertSame(4, $m->size());
    }

    public function testThrowWhenKeyDoesntMatchType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type stdClass, string given');

        $m = new ObjectKeys('stdClass', 'int');
        ($m)('stdClass', 42);
    }

    public function testThrowWhenValueDoesntMatchType()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type int, float given');

        $m = new ObjectKeys('stdClass', 'int');
        ($m)(new \stdClass, 42.0);
    }

    public function testGet()
    {
        $m = new ObjectKeys('stdClass', 'int');
        $m = ($m)($a = new \stdClass, 24);

        $this->assertSame(24, $m->get($a));
    }

    public function testThrowWhenGettingUnknownKey()
    {
        $this->expectException(ElementNotFound::class);

        (new ObjectKeys('stdClass', 'int'))->get(new \stdClass);
    }

    public function testContains()
    {
        $m = new ObjectKeys('stdClass', 'int');
        $m = ($m)($a = new \stdClass, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains($a));
    }

    public function testClear()
    {
        $m = new ObjectKeys('stdClass', 'float');
        $m = ($m)(new \stdClass, 42.0);

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
        $m = (new ObjectKeys('stdClass', 'int'))($a = new \stdClass, 42);
        $m2 = (new ObjectKeys('stdClass', 'int'))($a, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals(($m2)(new \stdClass, 66)));
        $this->assertFalse($m->equals(($m2)($a, 24)));
    }

    public function testFilter()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4);

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

    public function testThrowWhenGroupingAnEmptyMap()
    {
        $this->expectException(CannotGroupEmptyStructure::class);

        (new ObjectKeys('stdClass', 'int'))->groupBy(function() {});
    }

    public function testGroupBy()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4);

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
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 5);

        $k = $m->keys();
        $this->assertInstanceOf(Set::class, $k);
        $this->assertSame('stdClass', $k->type());
        $this->assertSame([$a, $b, $c, $d], unwrap($k));
        $this->assertTrue($k->equals($m->keys()));
    }

    public function testValues()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            (new \stdClass, 1)
            (new \stdClass, 2)
            (new \stdClass, 3)
            (new \stdClass, 5)
            (new \stdClass, 5);

        $v = $m->values();
        $this->assertInstanceOf(Sequence::class, $v);
        $this->assertSame('int', $v->type());
        $this->assertSame([1, 2, 3, 5, 5], unwrap($v));
        $this->assertTrue($v->equals($m->values()));
    }

    public function testMap()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4);

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
        $this->assertSame([$a, $b, $c, $d], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4], unwrap($m->values()));
        $this->assertSame([$a, $b, $c, $d], unwrap($m2->keys()));
        $this->assertSame([1, 12, 9, 14], unwrap($m2->values()));
    }

    public function testThrowWhenTryingToModifyValueTypeInTheMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type int, string given');

        (new ObjectKeys('stdClass', 'int'))
            (new \stdClass, 2)
            ->map(function(\stdClass $key, int $value) {
                return (string) $value;
            });
    }

    public function testThrowWhenTryingToModifyKeyTypeInTheMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type stdClass, int given');

        (new ObjectKeys('stdClass', 'int'))
            (new \stdClass, 2)
            ->map(function(\stdClass $key, int $value) {
                return new Pair(42, $value);
            });
    }

    public function testRemove()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4)
            ($e = new \stdClass, 5);

        $m2 = $m->remove(new \stdClass);
        $this->assertSame($m, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));

        $m2 = $m->remove($d);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([$a, $b, $c, $e], unwrap($m2->keys()));
        $this->assertSame([1, 2, 3, 5], unwrap($m2->values()));

        $m2 = $m->remove($e);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([$a, $b, $c, $d], unwrap($m2->keys()));
        $this->assertSame([1, 2, 3, 4], unwrap($m2->values()));

        $m2 = $m->remove($a);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(ObjectKeys::class, $m2);
        $this->assertSame([$a, $b, $c, $d, $e], unwrap($m->keys()));
        $this->assertSame([1, 2, 3, 4, 5], unwrap($m->values()));
        $this->assertSame([$b, $c, $d, $e], unwrap($m2->keys()));
        $this->assertSame([2, 3, 4, 5], unwrap($m2->values()));
    }

    public function testMerge()
    {
        $m = (new ObjectKeys(\stdClass::class, 'int'))
            ($s = new \stdClass, 24)
            ($s2 = new \stdClass, 42);
        $m2 = (new ObjectKeys(\stdClass::class, 'int'))
            ($s3 = new \stdClass, 24)
            ($s2, 66)
            ($s4 = new \stdClass, 42);

        $m3 = $m->merge($m2);
        $this->assertNotSame($m, $m3);
        $this->assertNotSame($m2, $m3);
        $this->assertInstanceOf(ObjectKeys::class, $m3);
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
        $m = (new ObjectKeys('stdClass', 'int'))
            ($a = new \stdClass, 1)
            ($b = new \stdClass, 2)
            ($c = new \stdClass, 3)
            ($d = new \stdClass, 4)
            ($e = new \stdClass, 5);

        $p = $m->partition(function(\stdClass $i, int $v) {
            return $v % 2 === 0;
        });

        $this->assertInstanceOf(Map::class, $p);
        $this->assertNotSame($p, $m);
        $this->assertSame('bool', $p->keyType());
        $this->assertSame(Map::class, $p->valueType());
        $this->assertSame(
            [true, false],
            unwrap($p->keys()),
        );
        $this->assertSame('stdClass', $p->get(true)->keyType());
        $this->assertSame('int', $p->get(true)->valueType());
        $this->assertSame('stdClass', $p->get(false)->keyType());
        $this->assertSame('int', $p->get(false)->valueType());
        $this->assertSame(
            [$b, $d],
            unwrap($p->get(true)->keys()),
        );
        $this->assertSame(
            [2, 4],
            unwrap($p->get(true)->values()),
        );
        $this->assertSame(
            [$a, $c, $e],
            unwrap($p->get(false)->keys()),
        );
        $this->assertSame(
            [1, 3, 5],
            unwrap($p->get(false)->values()),
        );
    }

    public function testReduce()
    {
        $m = (new ObjectKeys('stdClass', 'int'))
            ($a = new \stdClass, 4);

        $v = $m->reduce(
            42,
            function (float $carry, \stdClass $key, int $value): float {
                return $carry / $value;
            }
        );

        $this->assertSame(10.5, $v);
        $this->assertSame([$a], unwrap($m->keys()));
        $this->assertSame([4], unwrap($m->values()));
    }

    public function testEmpty()
    {
        $this->assertTrue((new ObjectKeys('stdClass', 'int'))->empty());
        $this->assertFalse((new ObjectKeys('stdClass', 'int'))(new \stdClass, 1)->empty());
    }

    public function testGenericObjectTypeAllowedAsKey()
    {
        $this->assertSame('object', (new ObjectKeys('object', 'int'))->keyType());
    }

    public function testToSequenceOf()
    {
        $map = (new ObjectKeys('object', 'int'))
            (new \stdClass, 2)
            (new \stdClass, 4);
        $sequence = $map->toSequenceOf('int', fn($k, $v) => yield $v);

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(
            [2, 4],
            unwrap($sequence),
        );
    }

    public function testToSetOf()
    {
        $map = (new ObjectKeys('object', 'int'))
            (new \stdClass, 2)
            (new \stdClass, 4);
        $set = $map->toSetOf('int', fn($k, $v) => yield $v);

        $this->assertInstanceOf(Set::class, $set);
        $this->assertSame(
            [2, 4],
            unwrap($set),
        );
    }

    public function testToMapOf()
    {
        $map = (new ObjectKeys('object', 'int'))
            ($a = new \stdClass, 2)
            ($b = new \stdClass, 4);
        $map = $map->toMapOf('int', 'object', fn($i, $j) => yield $j => $i);

        $this->assertInstanceOf(Map::class, $map);
        $this->assertCount(2, $map);
        $this->assertSame($a, $map->get(2));
        $this->assertSame($b, $map->get(4));
    }
}
