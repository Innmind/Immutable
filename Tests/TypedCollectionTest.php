<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Tests;

use Innmind\Immutable\Collection;
use Innmind\Immutable\TypedCollection;
use Innmind\Immutable\TypedCollectionInterface;
use Innmind\Immutable\StringPrimitive as S;
use Innmind\Immutable\PrimitiveInterface;

class TypedCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $c = new TypedCollection(S::class, [$s = new S('foo')]);

        $this->assertInstanceOf(TypedCollectionInterface::class, $c);
        $this->assertSame([$s], $c->toPrimitive());
        $this->assertSame(S::class, $c->getType());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each value must be an instance of "Innmind\Immutable\StringPrimitive"
     */
    public function testThrowWhenElementDoesntMatchType()
    {
        new TypedCollection(S::class, [0]);
    }

    public function testFilter()
    {
        $c = new TypedCollection(
            S::class,
            $d = [$f = new S('foo'), new S('bar'), $fb = new S('foobar')]
        );

        $result = $c->filter(function ($value, $key) {
            return strpos((string) $value, 'foo') !== false;
        });
        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertNotSame($c, $result);
        $this->assertSame([0 => $f, 2 => $fb], $result->toPrimitive());
        $this->assertSame($c->getType(), $result->getType());
        $this->assertSame($d, $c->toPrimitive());

        $c = new TypedCollection(
            I::class,
            $d = [new I(0), new I(1), new I(2), new I(0)]
        );

        $result = $c->filter();
        $this->assertInstanceOf(TypedCollection::class, $result);
        $this->assertNotSame($c, $result);
        $this->assertSame($d, $result->toPrimitive()); //as every object evaluates to true
        $this->assertSame($c->getType(), $result->getType());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testIntersect()
    {
        $c = new TypedCollection(
            S::class,
            ['foo' => $bar = new S('bar'), 'bar' => $baz = new S('baz')]
        );
        $c2 = new TypedCollection(
            S::class,
            $d2 = [new S('baz'), new S('bar'), 'foo' => new S('barbaz')]
        );

        $c3 = $c->intersect($c2);

        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(['foo' => $bar, 'bar' => $baz], $c3->toPrimitive());
        $this->assertSame(['foo' => $bar, 'bar' => $baz], $c->toPrimitive());
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenIntersectDifferentTypes()
    {
        (new TypedCollection(S::class, []))->intersect(new TypedCollection(I::class, []));
    }

    public function testChunk()
    {
        $c = new TypedCollection(
            I::class,
            $d = [$f = new I(0), $s = new I(0), $t = new I(0), $fr = new I(0), $fh = new I(0)]
        );

        $c2 = $c->chunk(2);

        $this->assertInstanceOf(Collection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertInstanceOf(TypedCollection::class, $c2[0]);
        $this->assertInstanceOf(TypedCollection::class, $c2[1]);
        $this->assertInstanceOf(TypedCollection::class, $c2[2]);
        $this->assertSame([$f, $s], $c2[0]->toPrimitive());
        $this->assertSame([$t, $fr], $c2[1]->toPrimitive());
        $this->assertSame([$fh], $c2[2]->toPrimitive());
        $this->assertSame($c->getType(), $c2[0]->getType());
        $this->assertSame($c->getType(), $c2[1]->getType());
        $this->assertSame($c->getType(), $c2[2]->getType());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testShift()
    {
        $c = new TypedCollection(
            S::class,
            [$f = new S('foo'), $b = new S('bar')]
        );

        $c2 = $c->shift();

        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertSame([$b], $c2->toPrimitive());
        $this->assertSame([$f, $b], $c->toPrimitive());
        $this->assertSame($c->getType(), $c2->getType());
    }

    public function testUintersect()
    {
        $c = new TypedCollection(
            S::class,
            ['a' => new S('green'), 'b' => new S('brown'), 'c' => new S('blue'), new S('red')]
        );
        $c2 = new TypedCollection(
            S::class,
            ['a' => new S('GREEN'), 'B' => new S('brown'), new S('yellow'), new S('red')]
        );

        $c3 = $c->uintersect($c2, 'strcasecmp');

        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(
            ['a' => $c['a'], 'b' => $c['b'], 0 => $c[0]],
            $c3->toPrimitive()
        );
        $this->assertSame(
            ['a' => $c['a'], 'b' => $c['b'], 'c' => $c['c'], $c[0]],
            $c->toPrimitive()
        );
        $this->assertSame(
            ['a' => $c2['a'], 'B' => $c2['B'], $c2[0], $c2[1]],
            $c2->toPrimitive()
        );
        $this->assertSame($c->getType(), $c3->getType());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenUintersectDifferentTypes()
    {
        (new TypedCollection(S::class, []))->intersect(
            new TypedCollection(I::class, []),
            'strcasecmp'
        );
    }

    public function testKeyIntersect()
    {
        $c = new TypedCollection(
            I::class,
            $d = ['blue' => new I(1), 'red' => new I(2), 'green' => new I(3), 'purple' => new I(4)]
        );
        $c2 = new TypedCollection(
            I::class,
            $d2 = ['green' => new I(5), 'blue' => new I(6), 'yellow' => new I(7), 'cyan' => new I(8)]
        );

        $c3 = $c->keyIntersect($c2);

        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame(
            ['blue' => $c['blue'], 'green' => $c['green']],
            $c3->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
        $this->assertSame($c->getType(), $c3->getType());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenKeyIntersectDifferentTypes()
    {
        (new TypedCollection(S::class, []))->keyIntersect(
            new TypedCollection(I::class, [])
        );
    }

    public function testMap()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(1), new I(2), new I(3), new I(4)]
        );

        $c2 = $c->map(function ($value) {
            return new I($value->toPrimitive() ** 2);
        });
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame(1, $c2[0]->toPrimitive());
        $this->assertSame(4, $c2[1]->toPrimitive());
        $this->assertSame(9, $c2[2]->toPrimitive());
        $this->assertSame(16, $c2[3]->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame($c->getType(), $c2->getType());
    }

    public function testPad()
    {
        $c = new TypedCollection(
            S::class,
            $d = [new S('foo'), new S('bar'), new S('foobar')]);

        $c2 = $c->pad(6, new S('foo'));
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c[0], $c2[0]);
        $this->assertSame($c[1], $c2[1]);
        $this->assertSame($c[2], $c2[2]);
        $this->assertInstanceOf(S::class, $c2[3]);
        $this->assertInstanceOf(S::class, $c2[4]);
        $this->assertInstanceOf(S::class, $c2[5]);
        $this->assertSame('foo', (string) $c2[3]);
        $this->assertSame('foo', (string) $c2[4]);
        $this->assertSame('foo', (string) $c2[5]);
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame($c->getType(), $c2->getType());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each value must be an instance of "Innmind\Immutable\StringPrimitive"
     */
    public function testThrowWhenPaddingWithIncompatibleValue()
    {
        (new TypedCollection(S::class, []))->pad(6, null);
    }

    public function testPop()
    {
        $c = new TypedCollection(
            S::class,
            $d = [new S('foo'), new S('bar')]
        );

        $c2 = $c->pop();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([$c[0]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame($c->getType(), $c2->getType());
    }

    public function testDiff()
    {
        $c = new TypedCollection(
            S::class,
            $d = ['a' => new S('green'), new S('red'), new S('blue'), new S('red')]
        );
        $c2 = new TypedCollection(
            S::class,
            $d2 = ['b' => $c['a'], new S('yellow'), $c[0]]);

        $c3 = $c->diff($c2);
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame([1 => $c[1]], $c3->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
        $this->assertSame($c->getType(), $c3->getType());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenDiffingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->diff(
            new TypedCollection(I::class, [])
        );
    }

    public function testPush()
    {
        $c = new TypedCollection(S::class, []);

        $c2 = $c->push($f = new S('foo'));
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame([$f], $c2->toPrimitive());
        $this->assertSame([], $c->toPrimitive());
        $this->assertSame($c->getType(), $c2->getType());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each value must be an instance of "Innmind\Immutable\StringPrimitive"
     */
    public function testThrowWhenPushinDifferentTypes()
    {
        (new TypedCollection(S::class, []))->push('');
    }

    public function testRand()
    {
        $c = new TypedCollection(
            S::class,
            $data = ['foo' => new S('bar'), 'baz' => new S('foobar')]
        );

        $c2 = $c->rand();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
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
        $c = new TypedCollection(S::class, [new S('foo')]);
        $c->rand(2);
    }

    public function testMerge()
    {
        $c = new TypedCollection(
            S::class,
            $d = ['color' => new S('red'), new S('2'), new S('4')]
        );
        $c2 = new TypedCollection(
            S::class,
            $d2 = [new S('a'), new S('b'), 'color' => new S('green'), 'shape' => new S('trapezoid'), $c[1]]
        );

        $c3 = $c->merge($c2);
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame($c2['color'], $c3['color']);
        $this->assertSame($c[0], $c3[0]);
        $this->assertSame($c[1], $c3[1]);
        $this->assertSame($c2[0], $c3[2]);
        $this->assertSame($c2[1], $c3[3]);
        $this->assertSame($c2['shape'], $c3['shape']);
        $this->assertSame($c2[2], $c3[4]);
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenMergingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->merge(
            new TypedCollection(I::class, [])
        );
    }

    public function testSlice()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(1), new I(2), new I(3), new I(4)]
        );

        $c2 = $c->slice(2);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$c[2], $c[3]], $c2->toPrimitive());

        $c2 = $c->slice(2, null, true);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([2 => $c[2], 3 => $c[3]], $c2->toPrimitive());

        $c2 = $c->slice(2, 1, true);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([2 => $c[2]], $c2->toPrimitive());
    }

    public function testUDiff()
    {
        $c = new TypedCollection(
            'stdClass',
            [
                $s1 = new \stdClass,
                $s2 = new \stdClass,
                $s3 = new \stdClass,
                $s4 = new \stdClass,
            ]
        );
        $c2 = new TypedCollection(
            'stdClass',
            [
                $s5 = new \stdClass,
                $s6 = new \stdClass,
            ]
        );
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

        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame([$s1, $s2], $c3->toPrimitive());
        $this->assertSame([$s1, $s2, $s3, $s4], $c->toPrimitive());
        $this->assertSame([$s5, $s6], $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenUDiffingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->udiff(
            new TypedCollection(I::class, []),
            'strcasecmp'
        );
    }

    public function testSplice()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(1), new I(2), new I(3), new I(4)]
        );

        $c2 = $c->splice(2, 0, [$i = new I(1)]);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            [$d[0], $d[1], $i, $d[2], $d[3]],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());

        $c2 = $c->splice(2, 2, [$i = new I(1)]);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$d[0], $d[1], $i], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUnique()
    {
        $c = new TypedCollection(
            I::class,
            $d = [$i = new I(1), new I(2), $i]
        );

        $c2 = $c->unique();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$i, $c[1]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testValues()
    {
        $c = new TypedCollection(
            I::class,
            $d = [1 => new I(1)]
        );

        $c2 = $c->values();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$c[1]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testReplace()
    {
        $c = new TypedCollection(
            S::class,
            $d1 = [new S('orange'), new S('banana'), new S('apple'), new S('raspberry')]
        );
        $c2 = new TypedCollection(
            S::class,
            $d2 = [0 => new S('pineapple'), 4 => new S('cherry')]
        );

        $c3 = $c->replace($c2);
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame(
            [$c2[0], $c[1], $c[2], $c[3], $c2[4]],
            $c3->toPrimitive()
        );
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenReplacingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->replace(
            new TypedCollection(I::class, [])
        );
    }

    public function testReverse()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(1), new I(2)]
        );

        $c2 = $c->reverse();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$c[1], $c[0]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUnshift()
    {
        $c = new TypedCollection(S::class, []);

        $c2 = $c->unshift($f = new S('foo'));
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$f], $c2->toPrimitive());
        $this->assertSame([], $c->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each value must be an instance of "Innmind\Immutable\StringPrimitive"
     */
    public function testThrowWhenUnshiftingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->unshift('');
    }

    public function testKeyDiff()
    {
        $c = new TypedCollection(
            I::class,
            $d1 = ['blue' => new I(1), 'red' => new I(2), 'green' => new I(3), 'purple' => new I(4)]
        );
        $c2 = new TypedCollection(
            I::class,
            $d2 = ['green' => new I(5), 'blue' => new I(6), 'yellow' => new I(7), 'cyan' => new I(8)]
        );

        $c3 = $c->keyDiff($c2);
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame(
            ['red' => $c['red'], 'purple' => $c['purple']],
            $c3->toPrimitive()
        );
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenKeyDiffingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->keyDiff(
            new TypedCollection(I::class, [])
        );
    }

    public function testUKeyDiff()
    {
        $c = new TypedCollection(
            I::class,
            $d1 = ['blue' => new I(1), 'red' => new I(2), 'green' => new I(3), 'purple' => new I(4)]
        );
        $c2 = new TypedCollection(
            I::class,
            $d2 = ['green' => new I(5), 'blue' => new I(6), 'yellow' => new I(7), 'cyan' => new I(8)]
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
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame(
            ['red' => $c['red'], 'purple' => $c['purple']],
            $c3->toPrimitive()
        );
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenUKeyDiffingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->ukeyDiff(
            new TypedCollection(I::class, []),
            'strcasecmp'
        );
    }

    public function testAssociativeDiff()
    {
        $c = new TypedCollection(
            S::class,
            $d1 = ['a' => new S('green'), 'b' => new S('brown'), 'c' => new S('blue'), new S('red')]
        );
        $c2 = new TypedCollection(
            S::class,
            $d2 = ['a' => new S('green'), new S('yellow'), new S('red')]
        );

        $c3 = $c->associativeDiff($c2);
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame(
            ['b' => $c['b'], 'c' => $c['c'], 0 => $c[0]],
            $c3->toPrimitive()
        );
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenAssociativeDiffingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->associativeDiff(
            new TypedCollection(I::class, [])
        );
    }

    public function testUKeyIntersect()
    {
        $c = new TypedCollection(
            I::class,
            $d1 = ['blue' => new I(1), 'red' => new I(2), 'green' => new I(3), 'purple' => new I(4)]
        );
        $c2 = new TypedCollection(
            I::class,
            $d2 = ['green' => new I(5), 'blue' => new I(6), 'yellow' => new I(7), 'cyan' => new I(8)]
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
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame(
            ['blue' => $c['blue'], 'green' => $c['green']],
            $c3->toPrimitive()
        );
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenUKeyIntersectingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->ukeyIntersect(
            new TypedCollection(I::class, []),
            'strcasecmp'
        );
    }

    public function testAssociativeIntersect()
    {
        $c = new TypedCollection(
            S::class,
            $d1 = ['a' => new S('green'), 'b' => new S('brown'), 'c' => new S('blue'), new S('red')]
        );
        $c2 = new TypedCollection(
            S::class,
            $d2 = ['a' => $c['a'], 'b' => new S('yellow'), new S('blue'), new S('red')]
        );

        $c3 = $c->associativeIntersect($c2);
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertNotSame($c2, $c3);
        $this->assertSame($c->getType(), $c3->getType());
        $this->assertSame(['a' => $c['a']], $c3->toPrimitive());
        $this->assertSame($d1, $c->toPrimitive());
        $this->assertSame($d2, $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\BadMethodCallException
     * @expectedExceptionMessage The given collection is not compatible
     */
    public function testThrowWhenAssociativeIntersectingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->associativeIntersect(
            new TypedCollection(I::class, [])
        );
    }

    public function testSort()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(4), new I(3), new I(2), new I(1)]
        );

        $c2 = $c->sort();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$c[3], $c[2], $c[1], $c[0]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());

        $c = new TypedCollection(
            S::class,
            $d = [new S('Orange1'), new S('orange2'), new S('Orange3'), new S('orange20')]);

        $c2 = $c->sort(TypedCollection::SORT_NATURAL | TypedCollection::SORT_FLAG_CASE);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            [$c[0], $c[1], $c[2], $c[3]],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testAssociativeSort()
    {
        $c = new TypedCollection(
            S::class,
            $d = ['d' => new S('lemon'), 'a' => new S('orange'), 'b' => new S('banana'), 'c' => new S('apple')]
        );

        $c2 = $c->associativeSort();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            ['c' => $c['c'], 'b' => $c['b'], 'd' => $c['d'], 'a' => $c['a']],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testKeySort()
    {
        $c = new TypedCollection(
            S::class,
            $d = ['d' => new S('lemon'), 'a' => new S('orange'), 'b' => new S('banana'), 'c' => new S('apple')]
        );

        $c2 = $c->keySort();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            ['a' => $c['a'], 'b' => $c['b'], 'c' => $c['c'], 'd' => $c['d']],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUkeySort()
    {
        $c = new TypedCollection(
            I::class,
            $d = ['John' => new I(1), 'the Earth' => new I(2), 'an apple' => new I(3), 'a banana' => new I(4)]
        );

        $c2 = $c->ukeySort(function ($a, $b) {
            $a = preg_replace('@^(a|an|the) @', '', $a);
            $b = preg_replace('@^(a|an|the) @', '', $b);

            return strcasecmp($a, $b);
        });
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            ['an apple' => $c['an apple'], 'a banana' => $c['a banana'], 'the Earth' => $c['the Earth'], 'John' => $c['John']],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testReverseSort()
    {
        $c = new TypedCollection(
            S::class,
            $d = [new S('lemon'), new S('orange'), new S('banana'), new S('apple')]
        );

        $c2 = $c->reverseSort();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$c[1], $c[0], $c[2], $c[3]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUsort()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(3), new I(2), new I(5), new I(6), new I(1)]
        );

        $c2 = $c->usort(function ($a, $b) {
            if ($a->toPrimitive() == $b->toPrimitive()) {
                return 0;
            }

            return ($a->toPrimitive() < $b->toPrimitive()) ? -1 : 1;
        });
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([$c[4], $c[1], $c[0], $c[2], $c[3]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testAssociativeReverseSort()
    {
        $c = new TypedCollection(
            S::class,
            $d = ['d' => new S('lemon'), 'a' => new S('orange'), 'b' => new S('banana'), 'c' => new S('apple')]
        );

        $c2 = $c->associativeReverseSort();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            ['a' => $c['a'], 'd' => $c['d'], 'b' => $c['b'], 'c' => $c['c']],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testKeyReverseSort()
    {
        $c = new TypedCollection(
            S::class,
            $d = ['d' => new S('lemon'), 'a' => new S('orange'), 'b' => new S('banana'), 'c' => new S('apple')]
        );

        $c2 = $c->keyReverseSort();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            ['d' => $c['d'], 'c' => $c['c'], 'b' => $c['b'], 'a' => $c['a']],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testUassociativeSort()
    {
        $c = new TypedCollection(
            I::class,
            $d = [
                'a' => new I(4),
                'b' => new I(8),
                'c' => new I(-1),
                'd' => new I(-9),
                'e' => new I(2),
                'f' => new I(5),
                'g' => new I(3),
                'h' => new I(-4)
            ]
        );

        $c2 = $c->uassociativeSort(function ($a, $b) {
            if ($a->toPrimitive() == $b->toPrimitive()) {
                return 0;
            }

            return ($a->toPrimitive() < $b->toPrimitive()) ? -1 : 1;
        });
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            [
                'd' => $c['d'],
                'h' => $c['h'],
                'c' => $c['c'],
                'e' => $c['e'],
                'g' => $c['g'],
                'a' => $c['a'],
                'f' => $c['f'],
                'b' => $c['b'],
            ],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testNaturalSort()
    {
        $c = new TypedCollection(
            S::class,
            $d = [new S('img12.png'), new S('img10.png'), new S('img2.png'), new S('img1.png')]
        );

        $c2 = $c->naturalSort();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(
            [3 => $c[3], 2 => $c[2], 1 => $c[1], 0 => $c[0]],
            $c2->toPrimitive()
        );
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testShuffle()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(1), new I(2), new I(3), new I(4)]
        );

        $c2 = $c->shuffle();
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame(4, $c2->count());
        $shuffled = $c2->toPrimitive();

        foreach ($d as $i) {
            $this->assertTrue(in_array($i, $shuffled, true));
        }
    }

    public function testTake()
    {
        $c = new TypedCollection(
            I::class,
            $d = [new I(1), new I(2), new I(3), new I(4)]
        );

        $c2 = $c->take(2);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([0, 1], array_keys($c2->toPrimitive()));
        $this->assertTrue(in_array($c2->toPrimitive()[0], $d, true));
        $this->assertTrue(in_array($c2->toPrimitive()[1], $d, true));

        $c3 = $c->take(2, true);
        $this->assertInstanceOf(TypedCollection::class, $c3);
        $this->assertNotSame($c, $c3);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(2, $c3->count());
        $this->assertTrue(in_array(array_values($c3->toPrimitive())[0], $d, true));
        $this->assertTrue(in_array(array_values($c3->toPrimitive())[1], $d, true));
    }



    public function testGrep()
    {
        $c = new TypedCollection(
            S::class,
            $d = [new S('1'), new S('1.0'), new S('foo')]
        );

        $c2 = $c->grep('/^(\d+)?\.\d+$/');
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([1 => $c[1]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());

        $c2 = $c->grep('/^(\d+)?\.\d+$/', true);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame([0 => $c[0], 2 => $c[2]], $c2->toPrimitive());
        $this->assertSame($d, $c->toPrimitive());
    }

    public function testSet()
    {
        $c = new TypedCollection(I::class, [new I(1)]);

        $c2 = $c->set(0, new I(2));
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertNotSame($c, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame(1, $c[0]->toPrimitive());
        $this->assertSame(2, $c2[0]->toPrimitive());
    }

    public function testContains()
    {
        $c = new TypedCollection(I::class, [new I(42)]);

        $this->assertTrue($c->contains($c[0]));
        $this->assertFalse($c->contains(new I(42)));
        $this->assertFalse($c->contains(42));
        $this->assertFalse($c->contains('42'));
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     * @expectedExceptionMessage Each value must be an instance of "Innmind\Immutable\StringPrimitive"
     */
    public function testThrowWhenSettingDifferentTypes()
    {
        (new TypedCollection(S::class, []))->set(0, '');
    }

    public function testWalk()
    {
        $c = new TypedCollection(
            S::class,
            $d = [new S('1'), new S('2'), new S('3'), new S('4')]
        );

        $c2 = $c->walk(function (&$value, $key) {
            $value = $value->append((string) $key);
        });
        $this->assertNotSame($c, $c2);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame('10', (string) $c2[0]);
        $this->assertSame('21', (string) $c2[1]);
        $this->assertSame('32', (string) $c2[2]);
        $this->assertSame('43', (string) $c2[3]);
    }

    public function testUnset()
    {
        $c = new TypedCollection(
            S::class,
            $d = [new S('1'), new S('2'), new S('3')]
        );

        $c2 = $c->unset(1);
        $this->assertNotSame($c, $c2);
        $this->assertInstanceOf(TypedCollection::class, $c2);
        $this->assertSame($c->getType(), $c2->getType());
        $this->assertSame($d, $c->toPrimitive());
        $this->assertSame([$d[0], 2 => $d[2]], $c2->toPrimitive());
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenUnsettingUnknownIndex()
    {
        (new TypedCollection(S::class, []))->unset(1);
    }

    public function testType()
    {
        $c = new TypedCollection(S::class, []);

        $this->assertSame($c->type(), $c->getType());
    }
}

class I implements PrimitiveInterface
{
    private $value;

    public function __construct($value)
    {
        if (!is_int($value)) {
            throw new TypeException('Value must be an integer');
        }

        $this->value = (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->value;
    }
}
