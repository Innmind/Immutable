# `innmind/black-box` sets

This package provides additional sets for [`innmind/black-box`](https://packagist.org/packages/innmind/black-box) so you can more easily generate: `Map`s, `Set`s and `Sequence`s.

For the 3 `::of()` method you can pass as last parameter an instance of `Innmind\BlackBox\Set\Intergers` to specify the range of elements to generate. By default it's between `0` and `100`, depending on the values you generate you may to lower the upper bound to reduce the memory footprint and speed up your tests.

## `Map`

```php
use Fixtures\Innmind\Immutable\Map;
use Innmind\BlackBox\Set;

/** @var Set<Innmind\Immutable\Map<int, string>> */
$set = Map::of(
    Set\Integers::any(),
    Set\Strings::any(),
);
```

## `Set`

```php
use Fixtures\Innmind\Immutable\Set;
use Innmind\BlackBox\Set as BSet;

/** @var BSet<Innmind\Immutable\Set<string>> */
$set = Set::of(
    BSet\Strings::any(),
);
```

## `Sequence`

```php
use Fixtures\Innmind\Immutable\Sequence;
use Innmind\BlackBox\Sequence;

/** @var Set<Innmind\Immutable\Sequence<string>> */
$set = Sequence::of(
    Sequence\Strings::any(),
);
```
