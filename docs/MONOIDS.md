# Monoids

Monoids describe a way to combine two values of a given type. A monoid contains an identity value that when combined with another value doesn't change its value. The combine operation has to be associative meaning `combine(a, combine(b, c))` is the same as `combine(combine(a, b), c)`.

A simple monoid is an addition because adding `0` (the identity value) to any other integer won't change the value and `add(1, add(2, 3))` is the the same result as `add(add(1, 2), 6)` (both return 6).

This library comes with a few monoids:
- `Innmind\Immutable\Monoid\Concat` to append 2 instances of `Innmind\Immutable\Str` together
- `Innmind\Immutable\Monoid\Append` to append 2 instances of `Innmind\Immutable\Sequence` together
- `Innmind\Immutable\Monoid\MergeSet` to append 2 instances of `Innmind\Immutable\Set` together
- `Innmind\Immutable\Monoid\MergeMap` to append 2 instances of `Innmind\Immutable\Map` together

## Create your own

To make sure your own monoid follows the laws this library comes with properties you can use in your test like so:

```php
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Properties\Innmind\Immutable\Monoid;

class YourMonoidTest extends TestCase
{
    use BlackBox;

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(static function($property) {
                $property->ensureHeldBy(new YourMonoid);
            });
    }

    public function testHoldProperties()
    {
        $this
            ->forAll(Monoid::properties($this->set(), $this->equals()))
            ->then(static function($properties) {
                $properties->ensureHeldBy(new YourMonoid);
            });
    }

    public function properties(): iterable
    {
        foreach (Monoid::list($this->set(), $this->equals()) as $property) {
            yield [$property];
        }
    }

    public function equals(): callable
    {
        return static fn($a, $b) => /* this callable is the way to check that 2 values are equal */;
    }

    private function set(): Set
    {
        // this Set must generate values that are of the type your monoid understands
        return /* an instance of Set */
    }
}

```
