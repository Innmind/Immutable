<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\Append,
    Monoid,
    Sequence,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Properties\Innmind\Immutable\Monoid as PMonoid;

class AppendTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(Monoid::class, Append::of());
    }

    public function testCombine()
    {
        $sequence = Append::of()->combine(
            Sequence::of(1, 3),
            Sequence::of(2, 4),
        );

        $this->assertInstanceOf(Sequence::class, $sequence);
        $this->assertSame(
            [1, 3, 2, 4],
            $sequence->toList(),
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
                $property->ensureHeldBy(Append::of());
            });
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(PMonoid::properties($this->set(), $this->equals()))
            ->then(static function($properties) {
                $properties->ensureHeldBy(Append::of());
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
        return static fn($a, $b) => $a->equals($b);
    }

    private function set(): Set
    {
        return Set\Decorate::immutable(
            static fn($values) => Sequence::of(...$values),
            Set\Sequence::of(Set\AnyType::any()),
        );
    }
}
