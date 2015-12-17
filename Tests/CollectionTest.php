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
}
