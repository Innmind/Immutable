<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

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

        $c2 = $c->chunk(2);

        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertInstanceOf(Collection::class, $c2[0]);
        $this->assertInstanceOf(Collection::class, $c2[1]);
        $this->assertInstanceOf(Collection::class, $c2[2]);
        $this->assertSame([0, 0], $c2[0]->toPrimitive());
        $this->assertSame([0, 0], $c2[1]->toPrimitive());
        $this->assertSame([0], $c2[2]->toPrimitive());
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

        $c2 = $c->pad(6, null);
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

        $c3 = $c->rand(2);
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

        $c2 = $c->slice(2, 1, true);
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

    public function testSort()
    {
        $c = new Collection([4, 3, 2, 1]);

        $c2 = $c->sort();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1, 2, 3, 4], $c2->toPrimitive());
        $this->assertSame([4, 3, 2, 1], $c->toPrimitive());

        $c = new Collection($d = ['Orange1', 'orange2', 'Orange3', 'orange20']);

        $c2 = $c->sort(Collection::SORT_NATURAL | Collection::SORT_FLAG_CASE);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['Orange1', 'orange2', 'Orange3', 'orange20'],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testAssociativeSort()
    {
        $c = new Collection(
            $d = ['d' => 'lemon', 'a' => 'orange', 'b' => 'banana', 'c' => 'apple']
        );

        $c2 = $c->associativeSort();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['c' => 'apple', 'b' => 'banana', 'd' => 'lemon', 'a' => 'orange'],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testKeySort()
    {
        $c = new Collection(
            $d = ['d' => 'lemon', 'a' => 'orange', 'b' => 'banana', 'c' => 'apple']
        );

        $c2 = $c->keySort();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['a' => 'orange', 'b' => 'banana', 'c' => 'apple', 'd' => 'lemon'],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUkeySort()
    {
        $c = new Collection(
            $d = ['John' => 1, 'the Earth' => 2, 'an apple' => 3, 'a banana' => 4]
        );

        $c2 = $c->ukeySort(function ($a, $b) {
            $a = preg_replace('@^(a|an|the) @', '', $a);
            $b = preg_replace('@^(a|an|the) @', '', $b);

            return strcasecmp($a, $b);
        });
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['an apple' => 3, 'a banana' => 4, 'the Earth' => 2, 'John' => 1],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testReverseSort()
    {
        $c = new Collection(
            $d = ['lemon', 'orange', 'banana', 'apple']
        );

        $c2 = $c->reverseSort();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(['orange', 'lemon', 'banana', 'apple'], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUsort()
    {
        $c = new Collection($d = [3, 2, 5, 6, 1]);

        $c2 = $c->usort(function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1, 2, 3, 5, 6], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testAssociativeReverseSort()
    {
        $c = new Collection(
            $d = ['d' => 'lemon', 'a' => 'orange', 'b' => 'banana', 'c' => 'apple']
        );

        $c2 = $c->associativeReverseSort();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['a' => 'orange', 'd' => 'lemon', 'b' => 'banana', 'c' => 'apple'],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testKeyReverseSort()
    {
        $c = new Collection(
            $d = ['d' => 'lemon', 'a' => 'orange', 'b' => 'banana', 'c' => 'apple']
        );

        $c2 = $c->keyReverseSort();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['d' => 'lemon', 'c' => 'apple', 'b' => 'banana', 'a' => 'orange'],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUassociativeSort()
    {
        $c = new Collection(
            $d = ['a' => 4, 'b' => 8, 'c' => -1, 'd' => -9, 'e' => 2, 'f' => 5, 'g' => 3, 'h' => -4]
        );

        $c2 = $c->uassociativeSort(function ($a, $b) {
            if ($a == $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            ['d' => -9, 'h' => -4, 'c' => -1, 'e' => 2, 'g' => 3, 'a' => 4, 'f' => 5, 'b' => 8],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testNaturalSort()
    {
        $c = new Collection(
            $d = ['img12.png', 'img10.png', 'img2.png', 'img1.png']
        );

        $c2 = $c->naturalSort();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(
            [3 => 'img1.png', 2 => 'img2.png', 1 => 'img10.png', 0 => 'img12.png'],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testFirst()
    {
        $c = new Collection([1, 2, 3]);

        $this->assertSame(1, $c->first());
        $this->assertSame([1, 2, 3], $c->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\OutOfBoundException
     * @expectedExceptionMessage There is no first item
     */
    public function testThrowWhenNoFirstItem()
    {
        $c = new Collection([]);
        $c->first();
    }

    public function testLast()
    {
        $c = new Collection([1, 2, 3]);

        $this->assertSame(3, $c->last());
        $this->assertSame([1, 2, 3], $c->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\OutOfBoundException
     * @expectedExceptionMessage There is no last item
     */
    public function testThrowWhenNoLastItem()
    {
        $c = new Collection([]);
        $c->last();
    }

    public function testEach()
    {
        $c = new Collection([1, 2, 3]);
        $count = 0;
        $c->each(function ($key, $value) use (&$count) {
            $count++;
            $this->assertSame($key, $value - 1);

            return 1;
        });
        $this->assertSame(3, $count);
        $this->assertSame([1, 2, 3], $c->toPrimitive());
    }

    public function testJoin()
    {
        $c = new Collection($d = ['foo', 'bar', 'baz']);

        $string = $c->join('|');
        $this->assertSame('foo|bar|baz', $string);
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testShuffle()
    {
        $c = new Collection($d = range(1, 10));

        $c2 = $c->shuffle();
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame(10, $c2->count());
        $shuffled = $c2->toPrimitive();

        foreach ($d as $i) {
            $this->assertTrue(in_array($i, $shuffled, true));
        }
    }

    public function testTake()
    {
        $c = new Collection($d = range(1, 10));

        $c2 = $c->take(2);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([0, 1], array_keys($c2->toPrimitive()));
        $this->assertTrue(in_array($c2->toPrimitive()[0], $d, true));
        $this->assertTrue(in_array($c2->toPrimitive()[1], $d, true));

        $c3 = $c->take(2, true);
        $this->assertInstanceOf(Collection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertSame(2, $c3->count());
        $this->assertTrue(in_array(array_values($c3->toPrimitive())[0], $d, true));
        $this->assertTrue(in_array(array_values($c3->toPrimitive())[1], $d, true));
    }

    public function testGrep()
    {
        $c = new Collection(['1', '1.0', 'foo']);

        $c2 = $c->grep('/^(\d+)?\.\d+$/');
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1 => '1.0'], $c2->toPrimitive());
        $this->assertSame(['1', '1.0', 'foo'], $c->toPrimitive());

        $c2 = $c->grep('/^(\d+)?\.\d+$/', true);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([0 => '1', 2 => 'foo'], $c2->toPrimitive());
        $this->assertSame(['1', '1.0', 'foo'], $c->toPrimitive());
    }

    public function testSet()
    {
        $c = new Collection([1]);

        $c2 = $c->set(0, 2);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([1], $c->toPrimitive());
        $this->assertSame([2], $c2->toPrimitive());
    }

    public function testContains()
    {
        $c = new Collection(['42', 24]);

        $this->assertTrue($c->contains(24));
        $this->assertFalse($c->contains(42));
        $this->assertTrue($c->contains('42'));
    }

    public function testCount()
    {
        $c = new Collection([1, 2, 3]);

        $this->assertSame(3, count($c));
    }

    public function testIteratorInterface()
    {
        $c = new Collection(range(1, 2));

        $this->assertSame(1, $c->current());
        $this->assertSame(0, $c->key());
        $this->assertSame(null, $c->next());
        $this->assertSame(2, $c->current());
        $this->assertSame(1, $c->key());
        $this->assertTrue($c->valid());
        $this->assertSame(null, $c->rewind());
        $this->assertSame(0, $c->key());
        $c->next();
        $c->next();
        $this->assertFalse($c->valid());
    }

    public function testArrayAccessInterface()
    {
        $c = new Collection([1, 2]);

        $this->assertTrue(isset($c[0]));
        $this->assertSame(1, $c[0]);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unknown index foo
     */
    public function testThrowWhenTryingToAccessUnknownElement()
    {
        $c = new Collection([]);

        $c['foo'];
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify an immutable collection
     */
    public function testThrowWhenTryingToAddAnElement()
    {
        $c = new Collection([]);
        $c['foo'] = 'bar';
    }

    /**
     * @expectedException Innmind\Immutable\Exception\LogicException
     * @expectedExceptionMessage You can't modify an immutable collection
     */
    public function testThrowWhenTryingToUnsetAnElement()
    {
        $c = new Collection([]);
        unset($c['foo']);
    }

    public function testGet()
    {
        $c = new Collection([1]);

        $this->assertSame(1, $c->get(0));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage Unknown index 0
     */
    public function testThrowWhenAccessingUnknownKey()
    {
        $c = new Collection([]);

        $c->get(0);
    }

    public function testWalk()
    {
        $c = new Collection([1, 2, 3, 4]);

        $c2 = $c->walk(function (&$value, $key) {
            $value *= $key;
        });
        $this->assertNotSame($c, $c2);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertSame([1, 2, 3, 4], $c->toPrimitive());
        $this->assertSame([0, 2, 6, 12], $c2->toPrimitive());
    }

    public function testUnset()
    {
        $c = new Collection([1, 2, 3]);

        $c2 = $c->unset(1);
        $this->assertNotSame($c, $c2);
        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertSame([1, 2, 3], $c->toPrimitive());
        $this->assertSame([1, 2 => 3], $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenUnsettingUnknownIndex()
    {
        (new Collection([]))->unset(1);
    }
}
