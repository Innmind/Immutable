# `Set`

A set is an unordered list of unique elements.

## `::of()`

```php
use Innmind\Immutable\Set;

/** @var Set<int> */
Set::of(1, 2, 3, $etc);
```

## `::defer()`

This named constructor is for advanced use cases where you want the data of your set to be loaded upon use only and not initialisation.

An example for such a use case is a set of log lines coming from a file:

```php
$set = Set::defer((function() {
    yield from readSomeFile('apache.log');
})());
```

The method ask a generator that will provide the elements. Once the elements are loaded they are kept in memory so you can run multiple operations on it without loading the file twice.

**Important**: beware of the case where the source you read the elements is not altered before the first use of the set.

## `::lazy()`

This is similar to `::defer()` with the exception that the elements are not kept in memory but reloaded upon each use.

```php
$set = Set::lazy(function() {
    yield from readSomeFile('apache.log');
});
```

**Important**: since the elements are reloaded each time the immutability responsability is up to you because the source may change or if you generate objects it will generate new objects each time (so if you make strict comparison it will fail).

## `::mixed()`

This is a shortcut for `::of(mixed ...$mixed)`.

## `::ints()`

This is a shortcut for `::of(int ...$ints)`.

## `::floats()`

This is a shortcut for `::of(float ...$floats)`.

## `::strings()`

This is a shortcut for `::of(string ...$strings)`.

## `::objects()`

This is a shortcut for `::of(object ...$objects)`.

## `->__invoke()`

Augment the set with a new element. If the element is already in the set nothing changes.

```php
$set = Set::ints(1);
$set = ($set)(2)(1);
$set->equals(Set::ints(1, 2));
```

## `->add()`

This is an alias for `->__invoke()`.

## `->size()`

This returns the number of elements in the set.

```php
$set = Set::ints(1, 4, 6);
$set->size(); // 3
```

## `->count()`

This is an alias for `->size()`, but you can also use the PHP function `\count` if you prefer.

```php
$set = Set::ints(1, 4, 6);
$set->count(); // 3
\count($set); // 3
```

## `->intersect()`

Create a new set with the elements that are also in the other set.

```php
$set = Set::ints(1, 2, 3)->intersect(Set::ints(2, 3, 4));
$set->equals(Set::ints(2, 3)); // true
```

## `->contains()`

Check if the element is present in the set.

```php
$set = Set::ints(1, 42, 3);
$set->contains(2); // false
$set->contains(42); // true
$set->contains('42'); // false but psalm will raise an error
```

## `->remove()`

Create a new set without the specified element.

```php
$set = Set::ints(1, 2, 3);
$set->remove(2)->equals(Set::ints(1, 3)); // true
```

## `->diff()`

This method will return a new set containing the elements that are not present in the other set.

```php
$set = Set::ints(1, 4, 6)->diff(Set::ints(1, 3, 6));
$set->equals(Set::ints(4)); // true
```

## `->equals()`

Check if two sets are identical.

```php
Set::ints(1, 2)->equals(Set::ints(2, 1)); // true
Set::ints()->equals(Set::strings()); // false but psalm will raise an error
```

## `->filter()`

Removes elements from the set that don't match the given predicate.

```php
$set = Set::ints(1, 2, 3, 4)->filter(fn($i) => $i % 2 === 0);
$set->equals(Set::ints(2, 4));
```

## `->foreach()`

Use this method to call a function for each element of the set. Since this structure is immutable it returns a `SideEffect` object, as its name suggest it is the only place acceptable to create side effects.

```php
$sideEffect = Set::strings('hello', 'world')->foreach(function(string $string): void {
    echo $string.' ';
});
```

## `->groupBy()`

This will create multiples sets with elements regrouped under the same key computed by the given function.

```php
$urls = Set::strings(
    'http://example.com',
    'http://example.com/foo',
    'https://example.com',
    'ftp://example.com',
);
/** @var Innmind\Immutable\Map<string, Set<string>> */
$map = $urls->groupBy(fn(string $url): string => \parse_url($url)['scheme']);
$map
    ->get('http')
    ->match(
        static fn($group) => $group,
        static fn() => Set::strings(),
    )
    ->equals(Set::strings('http://example.com', 'http://example.com/foo')); // true
$map
    ->get('https')
    ->match(
        static fn($group) => $group,
        static fn() => Set::strings(),
    )
    ->equals(Set::strings('https://example.com')); // true
$map
    ->get('ftp')
    ->match(
        static fn($group) => $group,
        static fn() => Set::strings(),
    )
    ->equals(Set::strings('ftp://example.com')); // true
```

## `->map()`

Create a new set with the exact same number of elements but modified by the given function.

```php
$ints = Set::ints(1, 2, 3);
$squares = $ints->map(fn($i) => $i**2);
$squares->equals(Set::ints(1, 4, 9)); // true
```

## `->partition()`

This method is similar to `->groupBy()` method but the map keys are always booleans. The difference is that here the 2 keys are always present whereas with `->groupBy()` it will depend on the original set.

```php
$set = Set::ints(1, 2, 3);
/** @var Map<bool, Set<int>> */
$map = $set->partition(fn($int) => $int % 2 === 0);
$map
    ->get(true)
    ->match(
        static fn($partition) => $partition,
        static fn() => Set::ints(),
    )
    ->equals(Set::ints(2)); // true
$map
    ->get(false)
    ->match(
        static fn($partition) => $partition,
        static fn() => Set::ints(),
    )
    ->equals(Set::ints(1, 3)); // true
```

## `->sort()`

It will transform the set into an ordered sequence.

```php
$sequence = Set::ints(1, 4, 2, 3)->sort(fn($a, $b) => $a <=> $b);
$sequence->equals(Sequence::ints(1, 2, 3, 4));
```

## `->merge()`

Create a new set with all the elements from both sets.

```php
$set = Set::ints(1, 2, 3)->merge(Set::ints(4, 2, 3));
$set->equals(Set::ints(1, 2, 3, 4));
```

## `->reduce()`

Iteratively compute a value for all the elements in the set.

```php
$set = Set::ints(1, 2, 3, 4);
$sum = $set->reduce(0, fn($sum, $int) => $sum + $int);
$sum; // 10
```

## `->clear()`

Create an empty new set of the same type. (To avoid to redeclare the types manually in a docblock)

```php
$set = Set::ints(1);
$set->clear()->size(); // 0
```

## `->empty()`

Tells whether there is at least one element or not.

```php
Set::ints()->empty(); // true
Set::ints(1)->empty(); // false
```

## `->find()`

Returns the first element that matches the predicate.

```php
$set = Set::ints(2, 4, 6, 8, 9, 10, 11);
/** @var Maybe<int> $firstOdd */
$firstOdd = $set->find(fn($i) => $i % 2 === 1);
$firstOdd; // could contain 9 or 11, because there is no ordering
```

## `->matches()`

Check if all the elements of the set matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Set::ints(1, 3, 5, 7)->matches($isOdd); // true
Set::ints(1, 3, 4, 5, 7)->matches($isOdd); // false
```

## `->any()`

Check if at least one element of the set matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Set::ints(1, 3, 5, 7)->any($isOdd); // true
Set::ints(1, 3, 4, 5, 7)->any($isOdd); // true
Set::ints(2, 4, 6, 8)->any($isOdd); // false
```
