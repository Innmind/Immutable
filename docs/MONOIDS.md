# Monoids

Monoids describe a way to combine two values of a given type. A monoid contains an identity value that when combined with another value doesn't change its value. The combine operation has to be associative meaning `combine(a, combine(b, c))` is the same as `combine(combine(a, b), c)`.

A simple monoid is an addition because adding `0` (the identity value) to any other integer won't change the value and `add(1, add(2, 3))` is the the same result as `add(add(1, 2), 3)` (both return 6).

This library comes with a few monoids:
- `Innmind\Immutable\Monoid\Concat` to append 2 instances of `Innmind\Immutable\Str` together
- `Innmind\Immutable\Monoid\Append` to append 2 instances of `Innmind\Immutable\Sequence` together
- `Innmind\Immutable\Monoid\MergeSet` to append 2 instances of `Innmind\Immutable\Set` together
- `Innmind\Immutable\Monoid\MergeMap` to append 2 instances of `Innmind\Immutable\Map` together

## Create your own

To make sure your own monoid follows the laws this library comes with properties you can use (via [`innmind/black-box`](https://github.com/Innmind/BlackBox/)) in your test like so:

```php
use Innmind\BlackBox\Set;
use Properties\Innmind\Immutable\Monoid;

return static function() {
    $equals = static fn($a, $b) => /* this callable is the way to check that 2 values are equal */;
    // this Set must generate values that are of the type your monoid understands
    $set = /* an instance of Set */;

    yield properties(
        'YourMonoid properties',
        Monoid::properties($set, $equals),
        Set\Elements::of(new YourMonoid),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield proof(
            'YourMonoid property',
            given($property),
            static fn($assert, $property) => $property->ensureHeldBy($assert, new YourMonoid),
        );
    }
};
```
