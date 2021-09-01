# `Map`

A map is an unordered list of pair of elements, think of it like an associative array or an `array<T, S>` in the [Psalm](http://psalm.dev) nomenclature. But with the added benefit that the keys can be of any type, even objects!

## `::of()`

```php
use Innmind\Immutable\Map;

/** @var Map<object, int> */
$map = Map::of();
```

The first type is for the keys and the second one for the values. This order is the same for all the methods below.

## `->__invoke()`

Augment the map with a new pair of elements. If the key already exist it will replace the value.

```php
$map = Map::of();
$map = ($map)(1, 2);
$map->equals(
    Map::of([1, 2]),
);
```

## `->put()`

This is an alias for `->__invoke()`.

## `->size()`

This returns the number of elements in the map.

```php
$map = Map::of([1, 2]);
$map->size(); // 1
```

## `->count()`

This is an alias for `->size()`, but you can also use the PHP function `\count` if you prefer.

```php
$map = Map::of([1, 2]);
$map->size(); // 1
\count($map); // 1
```

## `->get()`

Return an instance of [`Maybe`](maybe.md) that may contain the value associated to the given key (if it exists).

```php
$map = Map::of([1, 2], [3, 4]);
$map->get(1); // Maybe::just(2)
$map->get(2); // Maybe::nothing()
```

## `->contains()`

Check if the map contains a given key.

```php
$map = Map::of([1, 2], [3, 4]);
$map->contains(1); // true
$map->contains(2); // false
```

## `->clear()`

Return an empty new map of the same type. Useful to avoid to respecify the templates types of the map in a new docblock annotation.

```php
$map = Map::of([1, 2], [3, 4]);
$map->clear()->size(); // 0
```

## `->equals()`

Check if two maps are identical.

```php
$a = Map::of([1, 2], [3, 4]);
$b = Map::of([3, 4], [1, 2]);
$a->equals($b); // true
$a->equals(Map::of(); // false
```

## `->filter()`

Removes the pairs from the map that don't match the given predicate.

```php
$map = Map::of([1, 1], [3, 2]);
$map = $map->filter(fn($key, $value) => ($key + $value) % 2 === 0);
$map->equals(Map::of([1, 1]));
```

## `->foreach()`

Use this method to call a function for each pair of the map. Since this structure is immutable it returns a `SideEffect` object, as its name suggest it is the only place acceptable to create side effects.

```php
$sideEffect = Map::of(['hello', 'world'])->foreach(function(string $key, string $value): void {
    echo "$key $value"; // will print "hello world"
});
```

In itself the `SideEffect` object has no use except to avoid psalm complaining that the `foreach` method is not used.

## `->groupBy()`

This will create multiples maps with elements regrouped under the same key computed by the given function.

```php
$urls = Map::of(
    ['http://example.com', 1],
    ['http://example.com/foo', 1],
    ['https://example.com', 2],
    ['ftp://example.com', 4],
);
/** @var Innmind\Immutable\Map<string, Sequence<string>> */
$map = $urls->groupBy(fn(string $url, int $whatever): string => \parse_url($url)['scheme']);
$map
    ->get('http')
    ->match(
        static fn($group) => $group,
        static fn() => Map::of(),
    )
    ->equals(Map::of(
        ['http://example.com', 1],
        ['http://example.com/foo', 1]
    )); // true
$map
    ->get('https')
    ->match(
        static fn($group) => $group,
        static fn() => Map::of(),
    )
    ->equals(Map::of(['https://example.com', 2])); // true
$map
    ->get('ftp')
    ->match(
        static fn($group) => $group,
        static fn() => Map::of(),
    )
    ->equals(Map::of(['ftp://example.com', 4])); // true
```

## `->keys()`

Return a [`Set`](SET.md) of all the keys of the map.

```php
$keys = Map::of([24, 1], [42, 2])->keys();
$keys->equals(Set::of(24, 42)); // true
```

## `->values()`

Return a [`Sequence`](SEQUENCE.md) of all the values of the map.

```php
$values = Map::of([24, 1], [42, 2])->values();
$values->equals(Sequence::of(1, 2)); // true
```

**Note**: it returns a `Sequence` because it can contain duplicates, the order is not guaranteed as a map is not ordered.

## `->map()`

Create a new map of the same type with the exact same number of pairs but modified by the given function.

```php
$urls = Map::of(
    ['example.com', 1],
    ['github.com', 1],
    ['news.ycombinator.com', 1],
    ['reddit.com', 1],
);
$incremented = $map->map(fn($key, $value) => $value + 1);
$incremented->equals(
    Map::of(
        ['example.com', 2]
        ['github.com', 2]
        ['news.ycombinator.com', 2]
        ['reddit.com', 2]
    ),
);
```

## `->flatMap()`

This is similar to `->map()` but instead of returning a new value it returns a new `Map` for each value, all maps are merged to form only one `Map`.

This is usefull to generate multiple pairs for each initial pair or to modify the keys.

```php
$urls = Map::of(
    ['example.com', 1],
    ['github.com', 1],
    ['news.ycombinator.com', 1],
    ['reddit.com', 1],
);
$withScheme = $map->map(fn($key, $value) => Map::of(
    ["http://$key", $value],
    ["https://$key", $value],
));
$withScheme->equals(
    Map::of(
        ['http://example.com', 1],
        ['https://example.com', 1],
        ['http://github.com', 1],
        ['https://github.com', 1],
        ['http://news.ycombinator.com', 1],
        ['https://news.ycombinator.com', 1],
        ['http://reddit.com', 1],
        ['https://reddit.com', 1],
    ),
);
```

## `->remove()`

Remove the pair from the map with the given key.

```php
$map = Map::of([2, 3], [3, 4]);
$map->remove(3)->equals(Map::of([2, 3])); // true
```

## `->merge()`

Create a new map with all pairs from both maps. Pairs from the map in the argument will replace existing pairs from the original map.

```php
$a = Map::of([1, 2], [3, 4]);
$b = Map::of([5, 6], [3, 7]);
$a->merge($b)->equals(
    Map::of(
        [1, 2],
        [5, 6],
        [3, 7],
    ),
); // true
```

## `->partition()`

This method is similar to `->groupBy()` method but the map keys are always booleans. The difference is that here the 2 keys are always present whereas with `->groupBy()` it will depend on the original map.

```php
$map = Map::of([1, 2], [2, 3], [3, 3]);
/** @var Map<bool, Map<int, int>> */
$map = $map->partition(fn($key, $value) => ($key + $value) % 2 === 0);
$map
    ->get(true)
    ->match(
        static fn($partition) => $partition,
        static fn() => Map::of(),
    )
    ->equals(Map::of([3, 3])); // true
$map
    ->get(false)
    ->match(
        static fn($partition) => $partition,
        static fn() => Map::of(),
    )
    ->equals(Map::of([1, 2], [2, 3])); // true
```

## `->reduce()`

Iteratively compute a value for all the pairs in the map.

```php
$map = Map::of([1, 2], [2, 3], [3, 3]);
$sum = $map->reduce(0, fn($sum, $key, $value) => $sum + $key + $value);
$sum; // 14
```

## `->empty()`

Tells whether there is at least one pair or not.

```php
Map::of()->empty(); // true
Map::of([1, 2])->empty(); // false
```

## `->matches()`

Check if all the pairs of the map matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Map::of([1, 2], [3, 4])->matches(fn($key) => $isOdd($key)); // true
Map::of([1, 2], [3, 4])->matches(fn($key, $value) => $isOdd($value)); // false
```

## `->any()`

Check if at least one pair of the map matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Map::of([1, 2], [3, 4])->any(fn($key) => $isOdd($key)); // true
Map::of([1, 3], [3, 4])->any(fn($key, $value) => $isOdd($value)); // true
Map::of([1, 2], [3, 4])->any(fn($key, $value) => $isOdd($value)); // false
```
