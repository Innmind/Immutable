<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\{
    Map,
    MapInterface,
    SizeableInterface,
    SequenceInterface,
    Pair,
    StringPrimitive
};

class MapTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $m = new Map('int', 'float');

        $this->assertInstanceOf(MapInterface::class, $m);
        $this->assertInstanceOf(SizeableInterface::class, $m);
        $this->assertInstanceOf(\Countable::class, $m);
        $this->assertInstanceOf(\Iterator::class, $m);
        $this->assertInstanceOf(\ArrayAccess::class, $m);
        $this->assertSame('int', $m->keyType());
        $this->assertSame('float', $m->valueType());
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

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidType()
    {
        (new Map('int', 'int'))->put(42, 42.0);
    }

    public function testIterator()
    {
        $m = new Map('int', 'int');
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
        $m = new Map('stdClass', 'stdClass');
        $m = $m->put($k = new \stdClass, $v = new \stdClass);

        $this->assertTrue(isset($m[$k]));
        $this->assertSame($v, $m[$k]);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify a map
     */
    public function testThrowWhenInjectingData()
    {
        $m = new Map('int', 'int');
        $m[24] = 42;
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify a map
     */
    public function testThrowWhenDeletingData()
    {
        $m = new Map('int', 'int');
        $m = $m->put(24, 42);

        unset($m[24]);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\ElementNotFoundException
     */
    public function testThrowWhenUnknownOffset()
    {
        $m = new Map('int', 'int');
        $m[24];
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenKeyDoesntMatchType()
    {
        $m = new Map('int', 'int');
        $m->put('24', 42);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenValueDoesntMatchType()
    {
        $m = new Map('int', 'int');
        $m->put(24, 42.0);
    }

    public function testGet()
    {
        $m = new Map('int', 'int');
        $m = $m->put(23, 24);

        $this->assertSame(24, $m->get(23));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\ElementNotFoundException
     */
    public function testThrowWhenGettingUnknownKey()
    {
        (new Map('int', 'int'))->get(24);
    }

    public function testContains()
    {
        $m = new Map('int', 'int');
        $m = $m->put(23, 24);

        $this->assertFalse($m->contains(24));
        $this->assertTrue($m->contains(23));
    }

    public function testDrop()
    {
        $m = new Map('int', 'int');
        $m = $m
            ->put(23, 24)
            ->put(41, 42)
            ->put(65, 66);

        $m2 = $m->drop(2);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame(3, $m->size());
        $this->assertSame(1, $m2->size());
        $this->assertFalse($m2->contains(23));
        $this->assertFalse($m2->contains(41));
        $this->assertTrue($m2->contains(65));
    }

    public function testDropEnd()
    {
        $m = new Map('int', 'int');
        $m = $m
            ->put(23, 24)
            ->put(41, 42)
            ->put(65, 66);

        $m2 = $m->dropEnd(2);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame(3, $m->size());
        $this->assertSame(1, $m2->size());
        $this->assertTrue($m2->contains(23));
        $this->assertFalse($m2->contains(41));
        $this->assertFalse($m2->contains(65));
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
        $this->assertSame('int', $m2->keyType());
        $this->assertSame('float', $m2->valueType());
    }

    public function testEquals()
    {
        $m = (new Map('int', 'int'))->put(24, 42);
        $m2 = (new Map('int', 'int'))->put(24, 42);

        $this->assertTrue($m->equals($m2));
        $this->assertFalse($m->equals($m2->put(65, 66)));
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

    /**
     * @expectedException Innmind\Immutable\Exception\GroupEmptyMapException
     */
    public function testThrowWhenGroupingAnEmptyMap()
    {
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
        $this->assertSame('integer', $m2->keyType());
        $this->assertSame(SequenceInterface::class, $m2->valueType());
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertTrue($m2->contains(2));
        $this->assertSame(2, $m2->get(0)->size());
        $this->assertSame(1, $m2->get(1)->size());
        $this->assertSame(1, $m2->get(2)->size());
        $this->assertInstanceOf(Pair::class, $m2->get(0)->get(0));
        $this->assertInstanceOf(Pair::class, $m2->get(0)->get(1));
        $this->assertInstanceOf(Pair::class, $m2->get(1)->get(0));
        $this->assertInstanceOf(Pair::class, $m2->get(2)->get(0));
        $this->assertSame(0, $m2->get(1)->get(0)->key());
        $this->assertSame(1, $m2->get(1)->get(0)->value());
        $this->assertSame(1, $m2->get(0)->get(0)->key());
        $this->assertSame(2, $m2->get(0)->get(0)->value());
        $this->assertSame(2, $m2->get(2)->get(0)->key());
        $this->assertSame(3, $m2->get(2)->get(0)->value());
        $this->assertSame(4, $m2->get(0)->get(1)->key());
        $this->assertSame(5, $m2->get(0)->get(1)->value());
    }

    public function testFirst()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $p = $m->first();
        $this->assertInstanceOf(Pair::class, $p);
        $this->assertSame(0, $p->key());
        $this->assertSame(1, $p->value());
        $this->assertSame($p, $m->first());
    }

    public function testLast()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $p = $m->last();
        $this->assertInstanceOf(Pair::class, $p);
        $this->assertSame(4, $p->key());
        $this->assertSame(5, $p->value());
        $this->assertSame($p, $m->last());
    }

    public function testKeys()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $k = $m->keys();
        $this->assertInstanceOf(SequenceInterface::class, $k);
        $this->assertSame([0, 1, 2, 4], $k->toPrimitive());
        $this->assertSame($k, $m->keys());
    }

    public function testValues()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $v = $m->values();
        $this->assertInstanceOf(SequenceInterface::class, $v);
        $this->assertSame([1, 2, 3, 5], $v->toPrimitive());
        $this->assertSame($v, $m->values());
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
        $this->assertSame([0, 1, 2, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 5], $m->values()->toPrimitive());
        $this->assertSame([10, 1, 12, 14], $m2->keys()->toPrimitive());
        $this->assertSame([1, 4, 3, 5], $m2->values()->toPrimitive());
    }

    public function testTake()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->take(2);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertTrue($m2->contains(0));
        $this->assertTrue($m2->contains(1));
        $this->assertFalse($m2->contains(2));
        $this->assertFalse($m2->contains(4));
        $this->assertSame(1, $m2->get(0));
        $this->assertSame(2, $m2->get(1));
    }

    public function testTakeEnd()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $m2 = $m->takeEnd(2);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame($m->keyType(), $m2->keyType());
        $this->assertSame($m->valueType(), $m2->valueType());
        $this->assertSame(4, $m->size());
        $this->assertSame(2, $m2->size());
        $this->assertFalse($m2->contains(0));
        $this->assertFalse($m2->contains(1));
        $this->assertTrue($m2->contains(2));
        $this->assertTrue($m2->contains(4));
        $this->assertSame(3, $m2->get(2));
        $this->assertSame(5, $m2->get(4));
    }

    public function testJoin()
    {
        $m = (new Map('int', 'int'))
            ->put(0, 1)
            ->put(1, 2)
            ->put(2, 3)
            ->put(4, 5);

        $s = $m->join(', ');
        $this->assertInstanceOf(StringPrimitive::class, $s);
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
        $this->assertSame($m, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());

        $m2 = $m->remove(3);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());
        $this->assertSame([0, 1, 2, 4], $m2->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 5], $m2->values()->toPrimitive());

        $m2 = $m->remove(4);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());
        $this->assertSame([0, 1, 2, 3], $m2->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4], $m2->values()->toPrimitive());

        $m2 = $m->remove(0);
        $this->assertNotSame($m, $m2);
        $this->assertInstanceOf(Map::class, $m2);
        $this->assertSame([0, 1, 2, 3, 4], $m->keys()->toPrimitive());
        $this->assertSame([1, 2, 3, 4, 5], $m->values()->toPrimitive());
        $this->assertSame([1, 2, 3, 4], $m2->keys()->toPrimitive());
        $this->assertSame([2, 3, 4, 5], $m2->values()->toPrimitive());
    }
}
