<?php

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\Collection;
use Innmind\Immutable\CollectionInterface;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $c = new Collection(['foo']);

        $this->assertInstanceOf(CollectionInterface::class, $c);
        $this->assertSame(['foo'], $c->toPrimitive());
    }

    public function testFilter()
    {
        $c = new Collection(['foo', 'bar', 'foobar']);

        $result = $c->filter(function ($value, $key) {
            return strpos($value, 'foo') !== false;
        });
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotSame($c, $result);
        $this->assertSame([0 => 'foo', 2 => 'foobar'], $result->toPrimitive());
        $this->assertSame(['foo', 'bar', 'foobar'], $c->toPrimitive());

        $c = new Collection([0, 1, 2, 0]);

        $result = $c->filter();
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertNotSame($c, $result);
        $this->assertSame([1 => 1, 2 => 2], $result->toPrimitive());
        $this->assertSame([0, 1, 2, 0], $c->toPrimitive());
    }

    public function testIntersect()
    {
        $c = new Collection(['foo' => 'bar', 'bar' => 'baz']);
        $c2 = new Collection(['baz', 'bar', 'foo' => 'barbaz']);

        $c3 = $c->intersect($c2);

        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $c3->toPrimitive());
        $this->assertSame(['foo' => 'bar', 'bar' => 'baz'], $c->toPrimitive());
        $this->assertSame(['baz', 'bar', 'foo' => 'barbaz'], $c2->toPrimitive());
    }

    public function testChunk()
    {
        $c = new Collection([0, 0, 0, 0, 0]);

        $c2 = $c->chunk('2');

        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([[0, 0], [0, 0], [0]], $c2->toPrimitive());
        $this->assertSame([0, 0, 0, 0, 0], $c->toPrimitive());
    }

    public function testShift()
    {
        $c = new Collection(['foo', 'bar']);

        $c2 = $c->shift();

        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertSame(['bar'], $c2->toPrimitive());
        $this->assertSame(['foo', 'bar'], $c->toPrimitive());
    }

    public function testReduce()
    {
        $c = new Collection([1, 2, 3, 4]);

        $result = $c->reduce(function ($carry, $value) {
            return $carry * $value;
        }, 42);

        $this->assertSame(1008, $result);
        $this->assertSame([1, 2, 3, 4], $c->toPrimitive());
    }

    public function testSearch()
    {
        $c = new Collection([1, 2, 3]);

        $result = $c->search(2);
        $this->assertSame(1, $result);

        $result = $c->search('2');
        $this->assertFalse($result);

        $result = $c->search('2', false);
        $this->assertSame(1, $result);
    }

    public function testUintersect()
    {
        $c = new Collection(
            ['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']
        );
        $c2 = new Collection(
            ['a' => 'GREEN', 'B' => 'brown', 'yellow', 'red']
        );

        $c3 = $c->uintersect($c2, 'strcasecmp');

        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(
            ['a' => 'green', 'b' => 'brown', 0 => 'red'],
            $c3->toPrimitive()
        );
        $this->assertSame(
            ['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red'],
            $c->toPrimitive()
        );
        $this->assertSame(
            ['a' => 'GREEN', 'B' => 'brown', 'yellow', 'red'],
            $c2->toPrimitive()
        );
    }

    public function testKeyIntersect()
    {
        $c = new Collection(
            ['blue'  => 1, 'red'  => 2, 'green'  => 3, 'purple' => 4]
        );
        $c2 = new Collection(
            ['green' => 5, 'blue' => 6, 'yellow' => 7, 'cyan'   => 8]
        );

        $c3 = $c->keyIntersect($c2);

        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(['blue' => 1, 'green' => 3], $c3->toPrimitive());
        $this->assertSame(
            ['blue'  => 1, 'red'  => 2, 'green'  => 3, 'purple' => 4],
            $c->toPrimitive()
        );
        $this->assertSame(
            ['green' => 5, 'blue' => 6, 'yellow' => 7, 'cyan'   => 8],
            $c2->toPrimitive()
        );
    }

    public function testMap()
    {
        $c = new Collection([1, 2, 3, 4]);

        $c2 = $c->map(function ($value) {
            return $value ** 2;
        });
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1, 4, 9, 16], $c2->toPrimitive());
        $this->assertSame([1, 2, 3, 4], $c->toPrimitive());
    }

    public function testPad()
    {
        $c = new Collection(['foo', 'bar', 'foobar']);

        $c2 = $c->pad('6', null);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['foo', 'bar', 'foobar', null, null, null],
            $c2->toPrimitive()
        );
        $this->assertSame(['foo', 'bar', 'foobar'], $c->toPrimitive());
    }

    public function testPop()
    {
        $c = new Collection(['foo', 'bar']);

        $c2 = $c->pop();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(['foo'], $c2->toPrimitive());
        $this->assertSame(['foo', 'bar'], $c->toPrimitive());
    }

    public function testSum()
    {
        $c = new Collection([1, 2, 3]);

        $result = $c->sum();
        $this->assertSame(6, $result);
        $this->assertSame([1, 2, 3], $c->toPrimitive());
    }

    public function testDiff()
    {
        $c = new Collection(['a' => 'green', 'red', 'blue', 'red']);
        $c2 = new Collection(['b' => 'green', 'yellow', 'red']);

        $c3 = $c->diff($c2);
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame([1 => 'blue'], $c3->toPrimitive());
        $this->assertSame(
            ['a' => 'green', 'red', 'blue', 'red'],
            $c->toPrimitive()
        );
        $this->assertSame(
            ['b' => 'green', 'yellow', 'red'],
            $c2->toPrimitive()
        );
    }

    public function testFlip()
    {
        $c = new Collection(['oranges', 'apples', 'pears']);

        $c2 = $c->flip();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['oranges' => 0, 'apples' => 1, 'pears' => 2],
            $c2->toPrimitive()
        );
        $this->assertSame(['oranges', 'apples', 'pears'], $c->toPrimitive());
    }

    public function testKeys()
    {
        $c = new Collection([0 => 100, 'color' => 'red']);

        $c2 = $c->keys();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([0, 'color'], $c2->toPrimitive());
        $this->assertSame([0 => 100, 'color' => 'red'], $c->toPrimitive());

        $c = new Collection(['blue', 'red', 'green', 'blue', 'blue']);

        $c2 = $c->keys('blue');
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([0, 3, 4], $c2->toPrimitive());
        $this->assertSame(['blue', 'red', 'green', 'blue', 'blue'], $c->toPrimitive());

        $c = new Collection([0, 1, 2, 3]);

        $c2 = $c->keys(true, false);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1, 2, 3], $c2->toPrimitive());
        $this->assertSame([0, 1, 2, 3], $c->toPrimitive());
    }

    public function testPush()
    {
        $c = new Collection([]);

        $c2 = $c->push('foo');
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(['foo'], $c2->toPrimitive());
        $this->assertSame([], $c->toPrimitive());
    }

    public function testRand()
    {
        $c = new Collection($data = ['foo' => 'bar', 'baz' => 'foobar']);

        $c2 = $c->rand();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(1, count($c2));
        $prim = $c2->toPrimitive();
        $key = array_keys($prim)[0];
        $this->assertTrue(in_array($key, ['foo', 'baz'], true));
        $this->assertSame($data[$key], array_values($prim)[0]);

        $c3 = $c->rand('2');
        $this->assertSame(2, count($c3));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\OutOfBoundException
     * @expectedExceptionMessage Trying to return a wider collection than the current one
     */
    public function testThrowWhenInvalidRandLength()
    {
        $c = new Collection(['foo']);
        $c->rand(2);
    }

    public function testMerge()
    {
        $c = new Collection(['color' => 'red', 2, 4]);
        $c2 = new Collection(
            ['a', 'b', 'color' => 'green', 'shape' => 'trapezoid', 4]
        );

        $c3 = $c->merge($c2);
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(
            ['color' => 'green', 2, 4, 'a', 'b', 'shape' => 'trapezoid', 4],
            $c3->toPrimitive()
        );
        $this->assertSame(['color' => 'red', 2, 4], $c->toPrimitive());
        $this->assertSame(
            ['a', 'b', 'color' => 'green', 'shape' => 'trapezoid', 4],
            $c2->toPrimitive()
        );
    }

    public function testSlice()
    {
        $c = new Collection([1, 2, 3, 4]);

        $c2 = $c->slice(2);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([3, 4], $c2->toPrimitive());

        $c2 = $c->slice(2, null, true);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([2 => 3, 3 => 4], $c2->toPrimitive());

        $c2 = $c->slice(2, '1', true);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([2 => 3], $c2->toPrimitive());
    }

    public function testUDiff()
    {
        $c = new Collection([
            $s1 = new \stdClass,
            $s2 = new \stdClass,
            $s3 = new \stdClass,
            $s4 = new \stdClass,
        ]);
        $c2 = new Collection([
            $s5 = new \stdClass,
            $s6 = new \stdClass,
        ]);
        $s1->width = 11; $s1->height = 3;
        $s2->width = 7;  $s2->height = 1;
        $s3->width = 2;  $s3->height = 9;
        $s4->width = 5;  $s4->height = 7;

        $s5->width = 7;  $s5->height = 5;
        $s6->width = 9;  $s6->height = 2;

        $c3 = $c->udiff($c2, function ($a, $b) {
            $areaA = $a->width * $a->height;
            $areaB = $b->width * $b->height;

            if ($areaA < $areaB) {
                return -1;
            } elseif ($areaA > $areaB) {
                return 1;
            } else {
                return 0;
            }
        });

        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame([$s1, $s2], $c3->toPrimitive());
        $this->assertSame([$s1, $s2, $s3, $s4], $c->toPrimitive());
        $this->assertSame([$s5, $s6], $c2->toPrimitive());
    }

    public function testColumn()
    {
        $c = new Collection($d = [
            [
                'id' => 2135,
                'first_name' => 'John',
                'last_name' => 'Doe',
            ],
            [
                'id' => 3245,
                'first_name' => 'Sally',
                'last_name' => 'Smith',
            ],
            [
                'id' => 5342,
                'first_name' => 'Jane',
                'last_name' => 'Jones',
            ],
            [
                'id' => 5623,
                'first_name' => 'Peter',
                'last_name' => 'Doe',
            ],
        ]);

        $c2 = $c->column('first_name');
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(['John', 'Sally', 'Jane', 'Peter'], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());

        $c2 = $c->column('first_name', 'id');
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            [2135 => 'John', 3245 => 'Sally', 5342 => 'Jane', 5623 => 'Peter'],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testSplice()
    {
        $c = new Collection([1, 2, 3, 4]);

        $c2 = $c->splice(2, 0, 1);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1, 2, 1, 3, 4], $c2->toPrimitive());
        $this->assertSame([1, 2, 3, 4], $c->toPrimitive());

        $c2 = $c->splice(2, 2, 1);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1, 2, 1], $c2->toPrimitive());
        $this->assertSame([1, 2, 3, 4], $c->toPrimitive());
    }

    public function testUnique()
    {
        $c = new Collection([1, 2, 1]);

        $c2 = $c->unique();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1, 2], $c2->toPrimitive());
        $this->assertSame([1, 2, 1], $c->toPrimitive());
    }

    public function testValues()
    {
        $c = new Collection([1 => 1]);

        $c2 = $c->values();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1], $c2->toPrimitive());
        $this->assertSame([1 => 1], $c->toPrimitive());
    }

    public function testProduct()
    {
        $c = new Collection([1, 2, 3, 4]);

        $this->assertSame(24, $c->product());
    }

    public function testReplace()
    {
        $c = new Collection($d1 = ['orange', 'banana', 'apple', 'raspberry']);
        $c2 = new Collection($d2 = [0 => 'pineapple', 4 => 'cherry']);

        $c3 = $c->replace($c2);
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(
            ['pineapple', 'banana', 'apple', 'raspberry', 'cherry'],
            $c3->toPrimitive()
        );
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    public function testReverse()
    {
        $c = new Collection([1, 2]);

        $c2 = $c->reverse();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([2, 1], $c2->toPrimitive());
        $this->assertSame([1, 2], $c->toPrimitive());
    }

    public function testUnshift()
    {
        $c = new Collection([]);

        $c2 = $c->unshift('foo');
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(['foo'], $c2->toPrimitive());
        $this->assertSame([], $c->toPrimitive());
    }

    public function testKeyDiff()
    {
        $c = new Collection(
            $d1 = ['blue'  => 1, 'red'  => 2, 'green'  => 3, 'purple' => 4]
        );
        $c2 = new Collection(
            $d2 = ['green' => 5, 'blue' => 6, 'yellow' => 7, 'cyan'   => 8]
        );

        $c3 = $c->keyDiff($c2);
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(['red' => 2, 'purple' => 4], $c3->toPrimitive());
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    public function testUKeyDiff()
    {
        $c = new Collection(
            $d1 = ['blue'  => 1, 'red'  => 2, 'green'  => 3, 'purple' => 4]
        );
        $c2 = new Collection(
            $d2 = ['green' => 5, 'blue' => 6, 'yellow' => 7, 'cyan'   => 8]
        );

        $c3 = $c->ukeyDiff($c2, function ($key1, $key2) {
            if ($key1 == $key2) {
                return 0;
            } else if ($key1 > $key2) {
                return 1;
            } else {
                return -1;
            }
        });
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(['red' => 2, 'purple' => 4], $c3->toPrimitive());
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    public function testAssociativeDiff()
    {
        $c = new Collection(
            $d1 = ['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']
        );
        $c2 = new Collection(
            $d2 = ['a' => 'green', 'yellow', 'red']
        );

        $c3 = $c->associativeDiff($c2);
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(
            ['b' => 'brown', 'c' => 'blue', 0 => 'red'],
            $c3->toPrimitive()
        );
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    public function testHasKey()
    {
        $c = new Collection(['foo' => null, 'bar' => 'baz']);

        $this->assertTrue($c->hasKey('foo'));
        $this->assertTrue($c->hasKey('bar', false));
        $this->assertFalse($c->hasKey('foo', false));
    }

    public function testCountValues()
    {
        $c = new Collection([1, 1, 2, 3]);

        $c2 = $c->countValues();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1 => 2, 2 => 1, 3 => 1], $c2->toPrimitive());
        $this->assertSame([1, 1, 2, 3], $c->toPrimitive());
    }

    public function testUKeyIntersect()
    {
        $c = new Collection(
            $d1 = ['blue'  => 1, 'red'  => 2, 'green'  => 3, 'purple' => 4]
        );
        $c2 = new Collection(
            $d2 = ['green' => 5, 'blue' => 6, 'yellow' => 7, 'cyan'   => 8]
        );

        $c3 = $c->ukeyIntersect($c2, function ($key1, $key2) {
            if ($key1 == $key2) {
                return 0;
            } else if ($key1 > $key2) {
                return 1;
            } else {
                return -1;
            }
        });
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(['blue' => 1, 'green' => 3], $c3->toPrimitive());
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    public function testAssociativeIntersect()
    {
        $c = new Collection(
            $d1 = ['a' => 'green', 'b' => 'brown', 'c' => 'blue', 'red']
        );
        $c2 = new Collection(
            $d2 = ['a' => 'green', 'b' => 'yellow', 'blue', 'red']
        );

        $c3 = $c->associativeIntersect($c2);
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(['a' => 'green'], $c3->toPrimitive());
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }
}
