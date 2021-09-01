# `Map`

A map is an unordered list of pair of elements, think of it like an associative array or a `array<T, S>` in the [Psalm](http://psalm.dev) nomenclature. But with the added benefit that the keys can be of any type, even objects!

A map is always typed in order to be sure it only contains elements of the type you specified. If you try to add an element of a different type it will throw an error.

## `::of()`

```php
use Innmind\Immutable\Map;

/** @var Map<object, int> */
$map = Map::of('object', 'int');
```

The first type is for the keys and the second one for the values. This order is the same for all the methods below where you specified both types.

## `->isOfType()`

This method is here to help you know the map is of a certain type:

```php
$map = Map::of('object', 'int');
$map->isOfType('stdClass', 'int'); // false
$map->isOfType('object', 'float'); // false
$map->isOfType('object', 'int'); // true
```

## `->keyType()`

This returns the keys type you specified at initialisation.

```php
$map = Map::of('stdClass', 'int');
$map->keyType(); // 'stdClass'
```

## `->valueType()`

This returns the values type you specified at initialisation.

```php
$map = Map::of('stdClass', 'int');
$map->valueType(); // 'int'
```

## `->__invoke()`

Augment the map with a new pair of elements. If the key already exist it will replace the value.

```php
$map = Map::of('int', 'int');
$map = ($map)(1, 2);
$map->equals(
    Map::of('int', 'int')->put(1, 2),
);
```

## `->put()`

This is an alias for `->__invoke()`.

## `->size()`

This returns the number of elements in the map.

```php
$map = Map::of('int', 'int')(1, 2);
$map->size(); // 1
```

## `->count()`

This is an alias for `->size()`, but you can also use the PHP function `\count` if you prefer.

```php
$map = Map::of('int', 'int')(1, 2);
$map->size(); // 1
\count($map); // 1
```

## `->get()`

Return the value associated to the given key.

```php
$map = Map::of('int', 'int')(1, 2)(3, 4);
$map->get(1); // 2
$map->get(2); // throws Innmind\Immutable\Exception\ElementNotFound
```

## `->contains()`

Check if the map contains a given key.

```php
$map = Map::of('int', 'int')(1, 2)(3, 4);
$map->contains(1); // true
$map->contains(2); // false
```

## `->clear()`

Return an empty new map of the same type.

```php
$map = Map::of('int', 'int')(1, 2)(3, 4);
$map->clear()->size(); // 0
```

## `->equals()`

Check if two maps are identical.

```php
$a = Map::of('int', 'int')(1, 2)(3, 4);
$b = Map::of('int', 'int')(3, 4)(1, 2);
$a->equals($b); // true
$a->equals(Map::of('string', 'int')); // throws \TypeError
```

## `->filter()`

Removes the pairs from the map that don't match the given predicate.

```php
$map = Map::of('int', 'int')(1, 1)(3, 2);
$map = $map->filter(fn($key, $value) => ($key + $value) % 2 === 0);
$map->equals(Map::of('int', 'int')(1, 1));
```

## `->foreach()`

Use this method to call a function for each pair of the map. Since this method doesn't return anything it is the only place acceptable to create side effects.

```php
Map::of('string', 'string')('hello', 'world')->foreach(function(string $key, string $value): void {
    echo "$key $value";
});
```

## `->group()`

This will create multiples maps with elements regrouped under the same key computed by the given function.

```php
$urls = Map::of('string', 'int')
    ('http://example.com', 1)
    ('http://example.com/foo', 1)
    ('https://example.com', 2)
    ('ftp://example.com', 4);
/** @var Map<string, Map<string, string>> */
$map = $urls->group(
    'string',
    fn(string $url, int $whatever): string => \parse_url($url)['scheme'],
);
$map->get('http')->equals(
    Map::of('string', 'int')('http://example.com', 1)('http://example.com/foo', 1),
); // true
$map->get('https')->equals(
    Map::of('string', 'int')('https://example.com', 2),
); // true
$map->get('ftp')->equals(
    Map::of('string', 'int')('ftp://example.com', 4),
); // true
```

## `->groupBy()`

This is similar to the `->group()` method with the exception that the key type of the returned `Map` will be determined by the first computed key value.

Since the key type is computed you cannot call `->groupBy()` on an empty map, otherwise it will throw `Innmind\Immutable\Exception\CannotGroupEmptyStructure`.

```php
$urls = Map::of('string', 'int')
    ('http://example.com', 1)
    ('http://example.com/foo', 1)
    ('https://example.com', 2)
    ('ftp://example.com', 4);
/** @var Innmind\Immutable\Map<string, Sequence<string>> */
$map = $urls->groupBy(fn(string $url, int $whatever): string => \parse_url($url)['scheme']);
$map->get('http')->equals(
    Map::of('string', 'int')('http://example.com', 1)('http://example.com/foo', 1),
); // true
$map->get('https')->equals(
    Map::of('string', 'int')('https://example.com', 2),
); // true
$map->get('ftp')->equals(
    Map::of('string', 'int')('ftp://example.com', 4),
); // true
```

## `->keys()`

Return a [`Set`](SET.md) of all the keys of the map.

```php
$keys = Map::of('int', 'int')(24, 1)(42, 2)->keys();
$keys->equals(Set::of(24, 42)); // true
```

## `->values()`

Return a [`Sequence`](SEQUENCE.md) of all the values of the map.

```php
$values = Map::of('int', 'int')(24, 1)(42, 2)->values();
$values->equals(Sequence::of(1, 2)); // true
```

**Note**: it returns a `Sequence` because it can contains duplicates, the order is not guaranteed as a map is not ordered.

## `->map()`

Create a new map of the same type with the exact same number of pairs but modified by the given function.

```php
use Innmind\Immutable\Pair;

$urls = Map::of('string', 'int')
    ('example.com', 1)
    ('github.com', 1)
    ('news.ycombinator.com', 1)
    ('reddit.com', 1);
$incremented = $map->map(fn($key, $value) => $value + 1);
$incremented->equals(
    Map::of('string', 'int')
        ('example.com', 2)
        ('github.com', 2)
        ('news.ycombinator.com', 2)
        ('reddit.com', 2)
);
$withScheme = $map->map(fn($key, $value) => new Pair("http://$key", $value));
$withScheme->equals(
    Map::of('string', 'int')
        ('http://example.com', 1)
        ('http://github.com', 1)
        ('http://news.ycombinator.com', 1)
        ('http://reddit.com', 1)
);
```

## `->remove()`

Remove the pair from the map with the given key.

```php
$map = Map::of('int', 'int')(2, 3)(3, 4);
$map->remove(3)->equals(Map::of('int', 'int')(2, 3)); // true
```

## `->merge()`

Create a new map with all pairs from both maps. Pairs from the map in the argument will replace existing pairs from the original map.

```php
$a = Map::of('int', 'int')(1, 2)(3, 4);
$b = Map::of('int', 'int')(5, 6)(3, 7);
$a->merge($b)->equals(
    Map::of('int', 'int')
        (1, 2)
        (5, 6)
        (3, 7),
); // true
```

## `->partition()`

This method is similar to `->group()` method but the map keys are always booleans. The difference is that here the 2 keys are always present whereas with `->group()` it will depend on the original map.

```php
$map = Map::of('int', 'int')(1, 2)(2, 3)(3, 3);
/** @var Map<bool, Map<int, int>> */
$map = $map->partition(fn($key, $value) => ($key + $value) % 2 === 0);
$map->get(true)->equals(Map::of('int', 'int')(3, 3)); // true
$map->get(false)->equals(Map::of('int', 'int')(1, 2)(2, 3)); // true
```

## `->reduce()`

Iteratively compute a value for all the pairs in the map.

```php
$map = Map::of('int', 'int')(1, 2)(2, 3)(3, 3);
$sum = $map->reduce(0, fn($sum, $key, $value) => $sum + $key + $value);
$sum; // 14
```

## `->empty()`

Tells whether there is at least one pair or not.

```php
Map::of('int', 'int')->empty(); // true
Map::of('int', 'int')(1, 2)->empty(); // false
```

## `->toSequenceOf()`

Create a new sequence with the value computed from the pairs.

```php
$sequence = Map::of('int', 'int')(1, 2)(3, 4)->toSequenceOf(
    'int',
    function(int $key, int $value) {
        yield $key;
        yield $value;
    },
);
$sequence->equals(Sequence::of('int|string', 1, 2, 3, 4)); // true
```

## `->toSetOf()`

Similar to `->toSequenceOf()` but it returns a [`Set`](SET.md) instead.

```php
$set = Map::of('int', 'int')(1, 2)(3, 4)->toSetOf(
    'int',
    function(int $key, int $value) {
        yield $key;
        yield $value;
    },
);
$set->equals(Set::of('int|string', 1, '1', 2, '2', 3, '3')); // true
```

## `->toMapOf()`

Similar to `->toSequenceOf()` but it returns a `Map` instead.

```php
$map = Map::of('int', 'int')(1, 2)(3, 4)->toMapOf(
    'string',
    'int',
    function(int $key, int $value) {
        yield (string) $key => $int;
    },
);
$map->equals(
    Map::of('string', 'int')
        ('1', 2)
        ('3', 4)
); // true
```

## `->matches()`

Check if all the pairs of the map matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Map::of('int', 'int')(1, 2)(3, 4)->matches(fn($key) => $isOdd($key)); // true
Map::of('int', 'int')(1, 2)(3, 4)->matches(fn($key, $value) => $isOdd($value)); // false
```

## `->any()`

Check if at least one pair of the map matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Map::of('int', 'int')(1, 2)(3, 4)->any(fn($key) => $isOdd($key)); // true
Map::of('int', 'int')(1, 3)(3, 4)->any(fn($key, $value) => $isOdd($value)); // true
Map::of('int', 'int')(1, 2)(3, 4)->any(fn($key, $value) => $isOdd($value)); // false
```
