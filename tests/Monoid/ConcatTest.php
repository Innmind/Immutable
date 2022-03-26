<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\Concat,
    Monoid,
    Str,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Properties\Innmind\Immutable\Monoid as PMonoid;

class ConcatTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Monoid::class, new Concat);
    }

    public function testCombine()
    {
        $str = (new Concat)->combine(Str::of('foo'), Str::of('bar'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame(
            'foobar',
            $str->toString(),
        );
    }

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(static function($property) {
                $property->ensureHeldBy(new Concat);
            });
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(PMonoid::properties($this->set(), $this->equals()))
            ->then(static function($properties) {
                $properties->ensureHeldBy(new Concat);
            });
    }

    public function properties(): iterable
    {
        foreach (PMonoid::list($this->set(), $this->equals()) as $property) {
            yield [$property];
        }
    }

    public function equals(): callable
    {
        return static fn($a, $b) => $a->toString() === $b->toString();
    }

    private function set(): Set
    {
        return Set\Decorate::immutable(
            static fn($str) => Str::of($str),
            Set\Unicode::strings(),
        );
    }
}
