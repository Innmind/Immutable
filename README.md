# Immutable

|  `develop` |
|------------|
| [![codecov](https://codecov.io/gh/Innmind/Immutable/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Immutable) |
| [![Build Status](https://travis-ci.org/Innmind/Immutable.svg?branch=develop)](https://travis-ci.org/Innmind/Immutable) |

A set of classes to wrap PHP primitives to build immutable data.

Here are some examples of what you can do:

## Sequence

To be used to wrap an ordered list of elements (elements can be of mixed types).

```php
use Innmind\Immutable\Sequence;
use function Innmind\Immutable\unwrap;

$seq = Sequence::of('mixed', 24, 42, 'Hitchhiker', 'Magrathea');
$seq->get(2); // Hitchhiker
$another = $seq->drop(2);
unwrap($another); // [Hitchhiker, Magrathea]
unwrap($seq); // [24, 42, Hitchhiker, Magrathea]

//----
// this example demonstrates the lazyness capability of the sequence
// precisely here it's able to read a file line by line and echo the lines
// that are less than 42 characters long (without requiring to load the whole
// file in memory)
$someFile = fopen('some/file.txt', 'r');
$lines = Sequence::lazy('string', fn() => yield fgets($someFile))
    ->filter(fn($line) => strlen($line) < 42);
// at this point no reading to the file has been done because all methods
// returning a new instance of a sequence will pipeline the operations to do,
// allowing to chain complex logic while accessing the original data once and
// without the need to keep the discarded data along the pipeline in memory
$lines->foreach(fn($line) => echo($line));
```

For a complete list of methods check [`Sequence`](src/Sequence.php).

## Set

To be used as a collection of unordered elements (elements must be of the same type).

```php
use Innmind\Immutable\Set;

$set = Set::of('int', 24, 42);
$set->equals(Set::of('int', 24, 42)); // true
$set->add(42.0); // will throw as it's a float and not an integer
```

The type passed in the constructor can be any primitive type (more precisely any type having a `is_{type}` function) or any class name.

For a complete list of methods check [`Set`](src/Set.php).

## Map

To be used as a collection of key/value pairs (both keys and values must be of the same type).

```php
use Innmind\Immutable\Map;
use function Innmind\Immutable\unwrap;

$map = Map::of('stdClass', 'int')
    (new \stdClass, 42)
    ($key = new \stdClass, 24);
$map->size(); // 2, because it's 2 different instances
unwrap($map->values()); // [42, 24]
$map = $map->put($key, 66);
$map->size(); // 2
unwrap($map->values()); // [42, 66]
```

The types passed in the constructor can be any primitive type (more precisely any type having a `is_{type}` function) or any class name.

For a complete list of methods check [`Map`](src/Map.php).

**Note**: As a map is not a simple associative array, when you call `map` the return value can be an instance of [`Pair`](src/Pair.php). If this this the case, the key used to reference the original value will be replaced by the key from the `Pair` instance in the new `Map` instance.

## Strings

```php
use Innmind\Immutable\Str;

$var = Str::of('the hitchhiker\'s guide to the galaxy');
echo $var
    ->replace('galaxy', '42') // the hitchhiker's guide to the 42
    ->substring(18) // guide to the 42
    ->toUpper()
    ->toString(); // outputs: GUIDE TO THE 42
echo $var->toString(); // outputs: the hitchhiker\'s guide to the galaxy
```

## Regular expressions

```php
use Innmind\Immutable\{
    RegExp,
    Str,
};

$regexp = RegExp::of('/(?<i>\d+)/');
$regexp->matches(Str::of('foo123bar')); // true
$regexp->matches(Str::of('foobar')); // false
$regexp->capture(Str::of('foo123bar')); // Map<scalar, Str> with index `i` set to Str::of('123')
```
