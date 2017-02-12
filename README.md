# Immutable

| `master` | `develop` |
|----------|-----------|
|[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Immutable/build-status/master)|[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=develop) [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=develop) [![Build Status](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Immutable/build-status/develop)|

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/733063fc-bb9a-4329-9412-c805299fc62b/big.png)](https://insight.sensiolabs.com/projects/733063fc-bb9a-4329-9412-c805299fc62b)

A set of classes to wrap PHP primitives to build immutable data.

Here are some examples of what you can do:

## Sequence

To be used to wrap an ordered list of elements (elements can be of mixed types).

```php
use Innmind\Immutable\Sequence;

$seq = new Sequence(24, 42, 'Hitchhiker', 'Magrathea');
$seq->get(2); // Hitchhiker
$another = $seq->drop(2);
$another->toPrimitive(); // [Hitchhiker, Magrathea]
$seq->toPrimitive(); // [24, 42, Hitchhiker, Magrathea]
```

For a complete list of methods check [`SequenceInterface`](SequenceInterface.php).

## Set

To be used as a collection of unordered elements (elements must be of the same type).

```php
use Innmind\Immutable\Set;

$set = new Set('int');
$set = $set
    ->add(24)
    ->add(42);
$set->equals((new Set('int'))->add(24)->add(42)); // true
$set->add(42.0); // will throw as it's a float and not an integer
```

The type passed in the constructor can be any primitive type (more precisely any type having a `is_{type}` function) or any class name.

For a complete list of methods check [`SetInterface`](SetInterface.php).

## Map

To be used as a collection of key/value pairs (both keys and values must be of the same type).

```php
use Innmind\Immutable\Map;
use Innmind\Immutable\Symbol;

$map = new Map(Symbol::class, 'int');
$map = $map
    ->put(new Symbol('foo'), 42)
    ->put($symbol = new Symbol('foo'), 24);
$map->size(); // 2, because even if the symbols represent the same string it's 2 different instances
$map->values()->toPrimitive(); // [42, 24]
$map = $map->put($symbol, 66);
$map->size(); // 2
$map->values()->toPrimitive(); // [42, 66]
```

The types passed in the constructor can be any primitive type (more precisely any type having a `is_{type}` function) or any class name.

For a complete list of methods check [`MapInterface`](MapInterface.php).

**Note**: As a map is not a simple associative array, when you call `map` the return value can be an instance of [`Pair`](Pair.php). If this this the case, the key used to reference the original value will be replaced by the key from the `Pair` instance in the new `Map` instance.

## Strings

```php
use Innmind\Immutable\Str;

$var = new Str('the hitchhiker\'s guide to the galaxy');
echo $var
    ->replace('galaxy', '42') // the hitchhiker's guide to the 42
    ->substring(18) // guide to the 42
    ->toUpper(); // outputs: GUIDE TO THE 42
echo $var; // outputs: the hitchhiker\'s guide to the galaxy
```

## Range

```php
use Innmind\Immutable\NumericRange;

$range = new NumericRange(0, 10, 1);
$range->toPrimitive(); //[0, 1, 2, 3? 4, 5, 6, 7, 8, 9, 10]
```

`NumericRange` implements the `Iterator` interface and don't call the `range` function so you can build huge ranges as there's only the current range pointer being kept in the object.
