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
use Innmind\Immutable\StringPrimitive as S;

$var = new S('the hitchhiker\'s guide to the galaxy');
echo $var
    ->replace('galaxy', '42') // the hitchhiker's guide to the 42
    ->substring(18) // guide to the 42
    ->toUpper(); // outputs: GUIDE TO THE 42
echo $var; // outputs: the hitchhiker\'s guide to the galaxy
```

## Collections

```php
use Innmind\Immutable\Collection;

$coll = new Collection([4, 2, 1, 3]);
$coll2 = $coll
    ->shift()
    ->sort()
    ->map(function ($int) {
        return $int ** 2;
    });
var_dump($coll2->toPrimitive()); // [1, 4, 9]
var_dump($coll->toPrimitive()); // [4, 2, 1, 3]
```

## Typed collections

This is the same as the collections excepts the first parameter of the constructor tells the class each element must be of.

```php
use Innmind\Immutable\TypedCollection;
use Innmind\Immutable\StringPrimitive as S;
use Innmind\Immutable\InvalidArgumentException;
use Innmind\Immutable\BadMethodCallException;

$coll = new TypedCollection(S::class, [new S('foo')]); // you're sure each element is a `S` object
$coll->getType() === S::class; // true
$coll->unshift('foo'); // will throw `InvalidArgumentException` as it's not an `S` object
$coll = new TypedCollection(S::class, ['foo']); // will throw `InvalidArgumentException`

$coll = new TypedCollection(S::class, []);
$coll2 = $coll->merge(new TypedCollection('stdClass', [])); // will throw `BadMethodCallException` as both collections are not of the same type
```

## Object storages

```php
use Innmind\Immutable\ObjectStorage;

$storage = (new ObjectStorage)
    ->attach($myObject, 'some attached data')
    ->merge($anotherObjectStorage);
echo $storage[$myObject]; // outputs 'some attached data'
$storage->toPrimitive(); // instance of SplObjectStorage
```
