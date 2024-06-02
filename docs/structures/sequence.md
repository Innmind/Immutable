# `Sequence`

A sequence is an ordered list of elements, think of it like an array such as `[1, 'a', new stdClass]` or a `list<T>` in the [Psalm](http://psalm.dev) nomenclature.

## `::of()`

The `of` static method allows you to create a new sequence with all the elements passed as arguments.

```php
use Innmind\Immutable\Sequence;

/** @var Sequence<int> */
Sequence::of(1, 2, 3, $etc);
```

## `::defer()`

This named constructor is for advanced use cases where you want the data of your sequence to be loaded upon use only and not initialisation.

An example for such a use case is a sequence of log lines coming from a file:

```php
$sequence = Sequence::defer((function() {
    yield from readSomeFile('apache.log');
})());
```

The method ask a generator that will provide the elements. Once the elements are loaded they are kept in memory so you can run multiple operations on it without loading the file twice.

!!! warning ""
    Beware of the case where the source you read the elements is not altered before the first use of the sequence.

## `::lazy()`

This is similar to `::defer()` with the exception that the elements are not kept in memory but reloaded upon each use.

```php
$sequence = Sequence::lazy(function() {
    yield from readSomeFile('apache.log');
});
```

!!! warning ""
    Since the elements are reloaded each time the immutability responsability is up to you because the source may change or if you generate objects it will generate new objects each time (so if you make strict comparison it will fail).

## `::lazyStartingWith()`

Same as `::lazy()` except you don't need to manually build the generator.

```php
$sequence = Sequence::lazyStartingWith(1, 2, 3);
```

!!! note ""
    This is useful when you know the first items of the sequence and you'll `append` another lazy sequence at the end.

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

Augment the sequence with a new element.

```php
$sequence = Sequence::ints(1);
$sequence = ($sequence)(2);
$sequence->equals(Sequence::ints(1, 2));
```

## `->add()`

This is an alias for `->__invoke()`.

## `->size()`

This returns the number of elements in the sequence.

```php
$sequence = Sequence::ints(1, 4, 6);
$sequence->size(); // 3
```

## `->count()`

This is an alias for `->size()`, but you can also use the PHP function `\count` if you prefer.

```php
$sequence = Sequence::ints(1, 4, 6);
$sequence->count(); // 3
\count($sequence); // 3
```

## `->get()`

This method will return a [`Maybe`](maybe.md) object containing the element at the given index in the sequence. If the index doesn't exist it will an empty `Maybe` object.

```php
$sequence = Sequence::ints(1, 4, 6);
$sequence->get(1); // Maybe::just(4)
$sequence->get(3); // Maybe::nothing()
```

## `->diff()`

This method will return a new sequence containing the elements that are not present in the other sequence.

```php
$sequence = Sequence::ints(1, 4, 6)->diff(Sequence::ints(1, 3, 6));
$sequence->equals(Sequence::ints(4)); // true
```

## `->distinct()`

This removes any duplicates in the sequence.

```php
$sequence = Sequence::ints(1, 2, 1, 3)->distinct();
$sequence->equals(Sequence::ints(1, 2, 3)); // true
```

## `->drop()`

This removes the number of elements from the end of the sequence.

```php
$sequence = Sequence::ints(5, 4, 3, 2, 1)->drop(2);
$sequence->equals(Sequence::ints(3, 2, 1)); // true
```

## `->dropEnd()`

This removes the number of elements from the end of the sequence.

```php
$sequence = Sequence::ints(1, 2, 3, 4, 5)->drop(2);
$sequence->equals(Sequence::ints(1, 2, 3)); // true
```

## `->equals()`

Check if two sequences are identical.

```php
Sequence::ints(1, 2)->equals(Sequence::ints(1, 2)); // true
Sequence::ints()->equals(Sequence::strings()); // false but psalm will raise an error
```

## `->filter()`

Removes elements from the sequence that don't match the given predicate.

```php
$sequence = Sequence::ints(1, 2, 3, 4)->filter(fn($i) => $i % 2 === 0);
$sequence->equals(Sequence::ints(2, 4));
```

## `->keep()`

This is similar to `->filter()` with the advantage of psalm understanding the type in the new `Sequence`.

```php
use Innmind\Immutable\Predicate\Instance;

$sequence = Sequence::of(null, new \stdClass, 'foo')->keep(
    Instance::of('stdClass'),
);
$sequence; // Sequence<stdClass>
```

## `->exclude()`

Removes elements from the sequence that match the given predicate.

```php
$sequence = Sequence::ints(1, 2, 3, 4)->filter(fn($i) => $i % 2 === 0);
$sequence->equals(Sequence::ints(1, 3));
```

## `->foreach()`

Use this method to call a function for each element of the sequence. Since this structure is immutable it returns a `SideEffect` object, as its name suggest it is the only place acceptable to create side effects.

```php
$sideEffect = Sequence::strings('hello', 'world')->foreach(
    function(string $string): void {
        echo $string.' ';
    },
);
```

In itself the `SideEffect` object has no use except to avoid psalm complaining that the `foreach` method is not used.

## `->groupBy()`

This will create multiples sequences with elements regrouped under the same key computed by the given function.

```php
$urls = Sequence::strings(
    'http://example.com',
    'http://example.com/foo',
    'https://example.com',
    'ftp://example.com',
);
/** @var Innmind\Immutable\Map<string, Sequence<string>> */
$map = $urls->groupBy(fn(string $url): string => \parse_url($url)['scheme']);
$map
    ->get('http')
    ->match(
        static fn($group) => $group,
        static fn() => Sequence::strings(),
    )
    ->equals(Sequence::strings(
        'http://example.com',
        'http://example.com/foo',
    )); // true
$map
    ->get('https')
    ->match(
        static fn($group) => $group,
        static fn() => Sequence::strings(),
    )
    ->equals(Sequence::strings('https://example.com')); // true
$map
    ->get('ftp')
    ->match(
        static fn($group) => $group,
        static fn() => Sequence::strings(),
    )
    ->equals(Sequence::strings('ftp://example.com')); // true
```

## `->first()`

This is an alias for `->get(0)`.

## `->last()`

This is an alias for `->get(->size() - 1)`.

## `->contains()`

Check if the element is present in the sequence.

```php
$sequence = Sequence::ints(1, 42, 3);
$sequence->contains(2); // false
$sequence->contains(42); // true
$sequence->contains('42'); // false but psalm will raise an error
```

## `->indexOf()`

This will return a [`Maybe`](maybe.md) object containing the index number at which the first occurence of the element was found.

```php
$sequence = Sequence::ints(1, 2, 3, 2);
$sequence->indexOf(2); // Maybe::just(1)
$sequence->indexOf(4); // Maybe::nothing()
```

## `->indices()`

Create a new sequence of integers representing the indices of the original sequence.

```php
$sequence = Sequence::ints(1, 2, 3);
$sequence->indices()->equals(Sequence::ints(...\range(0, $sequence->size() - 1)));
```

## `->map()`

Create a new sequence with the exact same number of elements but modified by the given function.

```php
$ints = Sequence::ints(1, 2, 3);
$squares = $ints->map(fn($i) => $i**2);
$squares->equals(Sequence::ints(1, 4, 9)); // true
```

## `->flatMap()`

This is similar to `->map()` except that instead of returning a new value it returns a new sequence for each value, and each new sequence is appended together.

```php
$ints = Sequence::ints(1, 2, 3);
$squares = $ints->flatMap(fn($i) => Sequence::of($i, $i**2));
$squares->equals(Sequence::ints(1, 1, 2, 4, 3, 9)); // true
```

## `->pad()`

Add the same element to a new sequence in order that its size is at least the given one.

```php
$sequence = Sequence::ints(1, 2, 3);
$sequence->pad(2, 0)->equals(Sequence::ints(1, 2, 3)); // true
$sequence->pad(5, 0)->equals(Sequence::ints(1, 2, 3, 0, 0)); // true
```

## `->partition()`

This method is similar to `->groupBy()` method but the map keys are always booleans. The difference is that here the 2 keys are always present whereas with `->groupBy()` it will depend on the original sequence.

```php
$sequence = Sequence::ints(1, 2, 3);
/** @var Map<bool, Sequence<int>> */
$map = $sequence->partition(fn($int) => $int % 2 === 0);
$map
    ->get(true)
    ->match(
        static fn($partition) => $partition,
        static fn() => Sequence::ints(),
    )
    ->equals(Sequence::ints(2)); // true
$map
    ->get(false)
    ->match(
        static fn($partition) => $partition,
        static fn() => Sequence::ints(),
    )
    ->equals(Sequence::ints(1, 3)); // true
```

## `->slice()`

Return a new sequence with only the elements that were between the given indices. (The upper bound is not included)

```php
$sequence = Sequence::ints(4, 3, 2, 1);
$sequence->slice(1, 4)->equals(Sequence::ints(3, 2)); // true
```

## `->take()`

Create a new sequence with only the given number of elements from the start of the sequence.

```php
Sequence::ints(4, 3, 1, 0)->take(2)->equals(Sequence::ints(4, 3)); // true
```

## `->takeEnd()`

Similar to `->take()` but it starts from the end of the sequence

```php
Sequence::ints(4, 3, 1, 0)->takeEnd(2)->equals(Sequence::ints(1, 0)); // true
```

## `->append()`

Add all elements of a sequence at the end of another.

```php
$sequence = Sequence::ints(1, 2)->append(Sequence::ints(3, 4));
$sequence->equals(Sequence::ints(1, 2, 3, 4)); // true
```

## `->intersect()`

Create a new sequence with the elements that are also in the other sequence.

```php
$sequence = Sequence::ints(1, 2, 3)->intersect(Sequence::ints(2, 3, 4));
$sequence->equals(Sequence::ints(2, 3)); // true
```

## `->sort()`

Reorder the elements within the sequence.

```php
$sequence = Sequence::ints(4, 2, 3, 1);
$sequence = $sequence->sort(fn($a, $b) => $a <=> $b);
$sequence->equals(Sequence::ints(1, 2, 3, 4));
```

## `->fold()`

This is similar to the `reduce` method but only takes a [`Monoid`](../MONOIDS.md) as an argument.

```php
use Innmind\Immutable\Monoid\Concat;

$lines = Sequence::of("foo\n", "bar\n", 'baz')
    ->map(fn($line) => Str::of($line))
    ->fold(new Concat);

$lines->equals("foo\nbar\nbaz"); // true
```

## `->reduce()`

Iteratively compute a value for all the elements in the sequence.

```php
$sequence = Sequence::ints(1, 2, 3, 4);
$sum = $sequence->reduce(0, fn($sum, $int) => $sum + $int);
$sum; // 10
```

## `->clear()`

Create an empty new sequence of the same type. (To avoid to redeclare the types manually in a docblock)

```php
$sequence = Sequence::ints(1);
$sequence->clear()->size(); // 0
```

## `->reverse()`

Create a new sequence where the last element become the first one and so on.

```php
$sequence = Sequence::ints(1, 2, 3, 4);
$sequence->reverse()->equals(Sequence::ints(4, 3, 2, 1));
```

## `->empty()`

Tells whether there is at least one element or not.

```php
Sequence::ints()->empty(); // true
Sequence::ints(1)->empty(); // false
```

## `->toList()`

It returns a new `array` containing all the elements of the sequence.

## `->find()`

Returns a [`Maybe`](maybe.md) object containing the first element that matches the predicate.

```php
$sequence = Sequence::ints(2, 4, 6, 8, 9, 10, 11);
$firstOdd = $sequence->find(fn($i) => $i % 2 === 1);
$firstOdd; // Maybe::just(9)
$sequence->find(static fn() => false); // Maybe::nothing()
```

## `->matches()`

Check if all the elements of the sequence matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Sequence::ints(1, 3, 5, 7)->matches($isOdd); // true
Sequence::ints(1, 3, 4, 5, 7)->matches($isOdd); // false
```

## `->any()`

Check if at least one element of the sequence matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Sequence::ints(1, 3, 5, 7)->any($isOdd); // true
Sequence::ints(1, 3, 4, 5, 7)->any($isOdd); // true
Sequence::ints(2, 4, 6, 8)->any($isOdd); // false
```

## `->match()`

This is a similar approach to pattern matching allowing you to decompose a sequence by accessing the first element and the rest of the sequence.

```php
function sum(Sequence $ints): int
{
    return $ints->match(
        fn(int $head, Sequence $tail) => $head + sum($tail),
        fn() => 0,
    );
}

$result = sum(Sequence::of(1, 2, 3, 4));
$result; // 10
```

!!! warning ""
    For lazy sequences bear in mind that the values will be kept in memory while the first call to `->match` didn't return.

## `->zip()`

This method allows to merge 2 sequences into a new one by combining the values of the 2 into pairs.

```php
$firnames = Sequence::of('John', 'Luke', 'James');
$lastnames = Sequence::of('Doe', 'Skywalker', 'Kirk');

$pairs = $firnames
    ->zip($lastnames)
    ->toList();
$pairs; // [['John', 'Doe'], ['Luke', 'Skywalker'], ['James', 'Kirk']]
```

## `->safeguard()`

This method allows you to make sure all values conforms to an assertion before continuing using the sequence.

```php
$uniqueFiles = Sequence::of('a', 'b', 'c', 'a')
    ->safeguard(
        Set::strings()
        static fn(Set $names, string $name) => match ($names->contains($name)) {
            true => throw new \LogicException("$name is already used"),
            false => $names->add($name),
        },
    );
```

This example will throw because there is the value `a` twice.

This method is especially useful for deferred or lazy sequences because it allows to make sure all values conforms after this call whithout unwrapping the whole sequence first. The downside of this lazy evaluation is that some operations may start before reaching a non conforming value (example below).

```php
Sequence::lazyStartingWith('a', 'b', 'c', 'a')
    ->safeguard(
        Set::strings()
        static fn(Set $names, string $name) => match ($names->contains($name)) {
            true => throw new \LogicException("$name is already used"),
            false => $names->add($name),
        },
    )
    ->foreach(static fn($name) => print($name));
```

This example will print `a`, `b` and `c` before throwing an exception because of the second `a`. Use this method carefully.

## `->aggregate()`

This methods allows to rearrange the elements of the Sequence. This is especially useful for parsers.

An example would be to rearrange a list of chunks from a file into lines:

```php
// let's pretend this comes from a stream
$chunks = ['fo', "o\n", 'ba', "r\n", 'ba', "z\n"];
$lines = Sequence::of(...$chunks)
    ->map(Str::of(...))
    ->aggregate(static fn($a, $b) => $a->append($b->toString())->split("\n"))
    ->flatMap(static fn($chunk) => $chunk->split("\n"))
    ->map(static fn($line) => $line->toString())
    ->toList();
$lines; // ['foo', 'bar', 'baz', '']
```

!!! note ""
    The `flatMap` is here in case there is only one chunk in the sequence, in which case the `aggregate` is not called

## `->memoize()`

This method will load all the values in memory. This is useful only for a deferred or lazy `Sequence`, the other sequence will be unaffected.

```php
$sequence = Sequence::lazy(function() {
    $stream = \fopen('some-file', 'r');
    while (!\feof($stream)) {
        yield \fgets($stream);
    }
})
    ->map(static fn($line) => \strtoupper($line)) // still no line loaded here
    ->memoize(); // load all lines and apply strtoupper on each
```

## `->dropWhile()`

This removes all the elements from the start of the sequence while the condition returns `true`.

```php
$values = Sequence::of(0, 0, 0, 1, 2, 3, 0)
    ->dropWhile(static fn($i) => $i === 0)
    ->toList();
$values === [1, 2, 3, 0];
```

## `->takeWhile()`

This keeps all the elements from the start of the sequence while the condition returns `true`.

```php
$values = Sequence::of(1, 2, 3, 0, 4, 5, 6, 0)
    ->takeWhile(static fn($i) => $i === 0)
    ->toList();
$values === [1, 2, 3];
```
