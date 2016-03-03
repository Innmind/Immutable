# Immutable

| `master` | `develop` |
|----------|-----------|
|[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=master) [![Build Status](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/Immutable/build-status/master)|[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=develop) [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Immutable/?branch=develop) [![Build Status](https://scrutinizer-ci.com/g/Innmind/Immutable/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/Immutable/build-status/develop)|

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/733063fc-bb9a-4329-9412-c805299fc62b/big.png)](https://insight.sensiolabs.com/projects/733063fc-bb9a-4329-9412-c805299fc62b)

A set of classes to wrap PHP primitives to build immutable data.

Here are some examples of what you can do:

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
