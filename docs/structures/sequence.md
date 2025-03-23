# `Sequence`

A sequence is an ordered list of elements, think of it like an array such as `[1, 'a', new stdClass]` or a `list<T>` in the [Psalm](http://psalm.dev) nomenclature.

!!! info ""
    Methods with the :material-memory-arrow-down: symbol indicates that they will trigger loading the generator for deferred and lazy sequences.

## Named constructors

### `::of()`

The `of` static method allows you to create a new sequence with all the elements passed as arguments.

```php
use Innmind\Immutable\Sequence;

/** @var Sequence<int> */
Sequence::of(1, 2, 3, $etc);
```

### `::defer()`

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

### `::lazy()`

This is similar to `::defer()` with the exception that the elements are not kept in memory but reloaded upon each use.

```php
$sequence = Sequence::lazy(function() {
    yield from readSomeFile('apache.log');
});
```

!!! warning ""
    Since the elements are reloaded each time the immutability responsability is up to you because the source may change or if you generate objects it will generate new objects each time (so if you make strict comparison it will fail).

### `::lazyStartingWith()`

Same as `::lazy()` except you don't need to manually build the generator.

```php
$sequence = Sequence::lazyStartingWith(1, 2, 3);
```

!!! note ""
    This is useful when you know the first items of the sequence and you'll `append` another lazy sequence at the end.

### `::mixed()`

This is a shortcut for `::of(mixed ...$mixed)`.

### `::ints()`

This is a shortcut for `::of(int ...$ints)`.

### `::floats()`

This is a shortcut for `::of(float ...$floats)`.

### `::strings()`

This is a shortcut for `::of(string ...$strings)`.

### `::objects()`

This is a shortcut for `::of(object ...$objects)`.

## Add values

### `->__invoke()`

Augment the sequence with a new element.

```php
$sequence = Sequence::ints(1);
$sequence = ($sequence)(2);
$sequence->equals(Sequence::ints(1, 2));
```

### `->add()`

This is an alias for `->__invoke()`.

### `->append()`

Add all elements of a sequence at the end of another.

```php
$sequence = Sequence::ints(1, 2)->append(Sequence::ints(3, 4));
$sequence->equals(Sequence::ints(1, 2, 3, 4)); // true
```

### `->prepend()`

This is similar to `->append()` except the order is switched.

!!! success ""
    The main advantage of this method is when using lazy sequences. If you want to add elements at the beginning of a sequence but the rest may be lazy then you need to create a lazy sequence with your values and then append the other lazy sequence; but this reveals the underlying lazyness of the call and you need to be aware that it could be lazy.

    Instead by using this method you no longer have to be aware that the other sequence is lazy or not.

## Access values

### `->size()` :material-memory-arrow-down:

This returns the number of elements in the sequence.

```php
$sequence = Sequence::ints(1, 4, 6);
$sequence->size(); // 3
```

### `->count()` :material-memory-arrow-down:

This is an alias for `->size()`, but you can also use the PHP function `\count` if you prefer.

```php
$sequence = Sequence::ints(1, 4, 6);
$sequence->count(); // 3
\count($sequence); // 3
```

### `->get()`

This method will return a [`Maybe`](maybe.md) object containing the element at the given index in the sequence. If the index doesn't exist it will an empty `Maybe` object.

```php
$sequence = Sequence::ints(1, 4, 6);
$sequence->get(1); // Maybe::just(4)
$sequence->get(3); // Maybe::nothing()
```

### `->first()`

This is an alias for `->get(0)`.

### `->last()`

This is an alias for `->get(->size() - 1)`.

### `->contains()` :material-memory-arrow-down:

Check if the element is present in the sequence.

```php
$sequence = Sequence::ints(1, 42, 3);
$sequence->contains(2); // false
$sequence->contains(42); // true
$sequence->contains('42'); // false but psalm will raise an error
```

### `->indexOf()`

This will return a [`Maybe`](maybe.md) object containing the index number at which the first occurence of the element was found.

```php
$sequence = Sequence::ints(1, 2, 3, 2);
$sequence->indexOf(2); // Maybe::just(1)
$sequence->indexOf(4); // Maybe::nothing()
```

### `->find()`

Returns a [`Maybe`](maybe.md) object containing the first element that matches the predicate.

```php
$sequence = Sequence::ints(2, 4, 6, 8, 9, 10, 11);
$firstOdd = $sequence->find(fn($i) => $i % 2 === 1);
$firstOdd; // Maybe::just(9)
$sequence->find(static fn() => false); // Maybe::nothing()
```

### `->matches()` :material-memory-arrow-down:

Check if all the elements of the sequence matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Sequence::ints(1, 3, 5, 7)->matches($isOdd); // true
Sequence::ints(1, 3, 4, 5, 7)->matches($isOdd); // false
```

### `->any()` :material-memory-arrow-down:

Check if at least one element of the sequence matches the given predicate.

```php
$isOdd = fn($i) => $i % 2 === 1;
Sequence::ints(1, 3, 5, 7)->any($isOdd); // true
Sequence::ints(1, 3, 4, 5, 7)->any($isOdd); // true
Sequence::ints(2, 4, 6, 8)->any($isOdd); // false
```

### `->empty()` :material-memory-arrow-down:

Tells whether there is at least one element or not.

```php
Sequence::ints()->empty(); // true
Sequence::ints(1)->empty(); // false
```

## Transform values

### `->map()`

Create a new sequence with the exact same number of elements but modified by the given function.

```php
$ints = Sequence::ints(1, 2, 3);
$squares = $ints->map(fn($i) => $i**2);
$squares->equals(Sequence::ints(1, 4, 9)); // true
```

### `->flatMap()`

This is similar to `->map()` except that instead of returning a new value it returns a new sequence for each value, and each new sequence is appended together.

```php
$ints = Sequence::ints(1, 2, 3);
$squares = $ints->flatMap(fn($i) => Sequence::of($i, $i**2));
$squares->equals(Sequence::ints(1, 1, 2, 4, 3, 9)); // true
```

### `->zip()`

This method allows to merge 2 sequences into a new one by combining the values of the 2 into pairs.

```php
$firnames = Sequence::of('John', 'Luke', 'James');
$lastnames = Sequence::of('Doe', 'Skywalker', 'Kirk');

$pairs = $firnames
    ->zip($lastnames)
    ->toList();
$pairs; // [['John', 'Doe'], ['Luke', 'Skywalker'], ['James', 'Kirk']]
```

### `->aggregate()`

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

### `->chunk()`

This is a shortcut over [`aggregate`](#-aggregate). The same example can be shortened:

```php
// let's pretend this comes from a stream
$chunks = ['fo', "o\n", 'ba', "r\n", 'ba', "z\n"];
$lines = Sequence::of(...$chunks)
    ->map(Str::of(...))
    ->map(
        static fn($chunk) => $chunk
            ->toEncoding(Str\Encoding::ascii)
            ->split(),
    )
    ->chunk(4)
    ->map(static fn($chars) => $chars->dropEnd(1)) // to remove "\n"
    ->map(Str::of('')->join(...))
    ->map(static fn($line) => $line->toString())
    ->toList();
$lines; // ['foo', 'bar', 'baz', '']
```

This better accomodates to the case where the initial `Sequence` only contains a single value.

### `->indices()`

Create a new sequence of integers representing the indices of the original sequence.

```php
$sequence = Sequence::ints(1, 2, 3);
$sequence->indices()->equals(Sequence::ints(...\range(0, $sequence->size() - 1)));
```

## Filter values

### `->filter()`

Removes elements from the sequence that don't match the given predicate.

```php
$sequence = Sequence::ints(1, 2, 3, 4)->filter(fn($i) => $i % 2 === 0);
$sequence->equals(Sequence::ints(2, 4));
```

### `->keep()`

This is similar to `->filter()` with the advantage of psalm understanding the type in the new `Sequence`.

```php
use Innmind\Immutable\Predicate\Instance;

$sequence = Sequence::of(null, new \stdClass, 'foo')->keep(
    Instance::of('stdClass'),
);
$sequence; // Sequence<stdClass>
```

### `->exclude()`

Removes elements from the sequence that match the given predicate.

```php
$sequence = Sequence::ints(1, 2, 3, 4)->filter(fn($i) => $i % 2 === 0);
$sequence->equals(Sequence::ints(1, 3));
```

### `->take()`

Create a new sequence with only the given number of elements from the start of the sequence.

```php
Sequence::ints(4, 3, 1, 0)->take(2)->equals(Sequence::ints(4, 3)); // true
```

### `->takeEnd()`

Similar to `->take()` but it starts from the end of the sequence

```php
Sequence::ints(4, 3, 1, 0)->takeEnd(2)->equals(Sequence::ints(1, 0)); // true
```

### `->takeWhile()`

This keeps all the elements from the start of the sequence while the condition returns `true`.

```php
$values = Sequence::of(1, 2, 3, 0, 4, 5, 6, 0)
    ->takeWhile(static fn($i) => $i === 0)
    ->toList();
$values === [1, 2, 3];
```

### `->drop()`

This removes the number of elements from the end of the sequence.

```php
$sequence = Sequence::ints(5, 4, 3, 2, 1)->drop(2);
$sequence->equals(Sequence::ints(3, 2, 1)); // true
```

### `->dropEnd()` :material-memory-arrow-down:

This removes the number of elements from the end of the sequence.

```php
$sequence = Sequence::ints(1, 2, 3, 4, 5)->drop(2);
$sequence->equals(Sequence::ints(1, 2, 3)); // true
```

### `->dropWhile()`

This removes all the elements from the start of the sequence while the condition returns `true`.

```php
$values = Sequence::of(0, 0, 0, 1, 2, 3, 0)
    ->dropWhile(static fn($i) => $i === 0)
    ->toList();
$values === [1, 2, 3, 0];
```

### `->slice()`

Return a new sequence with only the elements that were between the given indices. (The upper bound is not included)

```php
$sequence = Sequence::ints(4, 3, 2, 1);
$sequence->slice(1, 4)->equals(Sequence::ints(3, 2)); // true
```

### `->diff()`

This method will return a new sequence containing the elements that are not present in the other sequence.

```php
$sequence = Sequence::ints(1, 4, 6)->diff(Sequence::ints(1, 3, 6));
$sequence->equals(Sequence::ints(4)); // true
```

### `->intersect()`

Create a new sequence with the elements that are also in the other sequence.

```php
$sequence = Sequence::ints(1, 2, 3)->intersect(Sequence::ints(2, 3, 4));
$sequence->equals(Sequence::ints(2, 3)); // true
```

### `->distinct()`

This removes any duplicates in the sequence.

```php
$sequence = Sequence::ints(1, 2, 1, 3)->distinct();
$sequence->equals(Sequence::ints(1, 2, 3)); // true
```

## Extract values

### `->toList()` :material-memory-arrow-down:

It returns a new `array` containing all the elements of the sequence.

### `->match()` :material-memory-arrow-down:

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

### `->fold()` :material-memory-arrow-down:

This is similar to the `reduce` method but only takes a [`Monoid`](../MONOIDS.md) as an argument.

```php
use Innmind\Immutable\Monoid\Concat;

$lines = Sequence::of("foo\n", "bar\n", 'baz')
    ->map(fn($line) => Str::of($line))
    ->fold(new Concat);

$lines->equals("foo\nbar\nbaz"); // true
```

### `->reduce()` :material-memory-arrow-down:

Iteratively compute a value for all the elements in the sequence.

```php
$sequence = Sequence::ints(1, 2, 3, 4);
$sum = $sequence->reduce(0, fn($sum, $int) => $sum + $int);
$sum; // 10
```

### `->sink()` :material-memory-arrow-down:

This is similar to [`->reduce`](#-reduce) except you decide on each iteration it you want to continue reducing or not.

This is useful for long sequences (mainly lazy ones) where you need to reduce until you find some value in the `Sequence` or the reduced value matches some condition. This avoids iterating over values you know for sure you won't need.

=== "By hand"
    ```php
    use Innmind\Immutable\Sequence\Sink\Continuation;

    $sequence = Sequence::of(1, 2, 3, 4, 5);
    $sum = $sequence
        ->sink(0)
        ->until(static fn(
            int $sum,
            int $i,
            Continuation $continuation,
        ) => match (true) {
            $sum > 5 => $continuation->stop($sum),
            default => $continuation->continue($sum + $i),
        });
    ```

    Here `#!php $sum` is `#!php 6` and the `Sequence` stopped iterating on the 4th value.

=== "Maybe"
    ```php
    $sequence = Sequence::of(1, 2, 3, 4, 5);
    $sum = $sequence
        ->sink(0)
        ->maybe(static fn(int $sum, int $i) => match (true) {
            $sum > 5 => Maybe::nothing(),
            default => Maybe::just($sum + $i),
        })
        ->match(
            static fn(int $sum) => $sum,
            static fn() => null,
        );
    ```

    Instead of manually specifying if we want to continue or not, it's inferred by the content of the `Maybe`.

    Here the `#!php $sum` is `#!php null` because on the 4th iteration we return a `#!php Maybe::nothing()`.

    !!! warning ""
        Bear in mind that the carried value is lost when an iteration returns `#!php Maybe::nothing()`.

        If you need to still have access to the carried value you should use `#!php ->sink()->either()` and place the carried value on the left side.

    ??? abstract
        In essence this allows the transformation of `Sequence<Maybe<T>>` to `Maybe<Sequence<T>>`.

=== "Either"
    ```php
    $sequence = Sequence::of(1, 2, 3, 4, 5);
    $sum = $sequence
        ->sink(0)
        ->either(static fn(int $sum, int $i) => match (true) {
            $sum > 5 => Either::left($sum),
            default => Either::right($sum + $i),
        })
        ->match(
            static fn(int $sum) => $sum,
            static fn(int $sum) => $sum,
        );
    ```

    Instead of manually specifying if we want to continue or not, it's inferred by the content of the `Either`.

    Here the `#!php $sum` is `#!php 6` because on the 4th iteration we return an `#!php Either::left()` with the carried sum from the previous iteration.

    ??? abstract
        In essence this allows the transformation of `Sequence<Either<E, T>>` to `Either<E, Sequence<T>>`.

=== "Attempt"
    ```php
    $sequence = Sequence::of(1, 2, 3, 4, 5);
    $sum = $sequence
        ->sink(0)
        ->attempt(static fn(int $sum, int $i) => match (true) {
            $sum > 5 => Attempt::error(new \Exception('sum too high')),
            default => Attempt::result($sum + $i),
        })
        ->match(
            static fn(int $sum) => $sum,
            static fn(\Exception $e) => null,
        );
    ```

    Instead of manually specifying if we want to continue or not, it's inferred by the content of the `Attempt`.

    Here the `#!php $sum` is `#!php null` because on the 4th iteration we return a `#!php Attempt::error()`.

    !!! warning ""
        Bear in mind that the carried value is lost when an iteration returns `#!php Attempt::error()`. Unless you attach the value to the exception.

    ??? abstract
        In essence this allows the transformation of `Sequence<Attempt<T>>` to `Attempt<Sequence<T>>`.

## Misc.

### `->equals()` :material-memory-arrow-down:

Check if two sequences are identical.

```php
Sequence::ints(1, 2)->equals(Sequence::ints(1, 2)); // true
Sequence::ints()->equals(Sequence::strings()); // false but psalm will raise an error
```

### `->foreach()` :material-memory-arrow-down:

Use this method to call a function for each element of the sequence. Since this structure is immutable it returns a `SideEffect` object, as its name suggest it is the only place acceptable to create side effects.

```php
$sideEffect = Sequence::strings('hello', 'world')->foreach(
    function(string $string): void {
        echo $string.' ';
    },
);
```

In itself the `SideEffect` object has no use except to avoid psalm complaining that the `foreach` method is not used.

### `->groupBy()` :material-memory-arrow-down:

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

### `->pad()`

Add the same element to a new sequence in order that its size is at least the given one.

```php
$sequence = Sequence::ints(1, 2, 3);
$sequence->pad(2, 0)->equals(Sequence::ints(1, 2, 3)); // true
$sequence->pad(5, 0)->equals(Sequence::ints(1, 2, 3, 0, 0)); // true
```

### `->partition()` :material-memory-arrow-down:

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

### `->sort()`

Reorder the elements within the sequence.

```php
$sequence = Sequence::ints(4, 2, 3, 1);
$sequence = $sequence->sort(fn($a, $b) => $a <=> $b);
$sequence->equals(Sequence::ints(1, 2, 3, 4));
```

### `->clear()`

Create an empty new sequence of the same type. (To avoid to redeclare the types manually in a docblock)

```php
$sequence = Sequence::ints(1);
$sequence->clear()->size(); // 0
```

### `->reverse()`

Create a new sequence where the last element become the first one and so on.

```php
$sequence = Sequence::ints(1, 2, 3, 4);
$sequence->reverse()->equals(Sequence::ints(4, 3, 2, 1));
```

### `->toSet()`

It's like [`->distinct()`](#-distinct) except it returns a [`Set`](set.md) instead of a `Sequence`.

### `->toIdentity()`

This method wraps the sequence in an [`Identity` monad](identity.md).

Let's say you have a sequence of strings representing the parts of a file and you want to build a file object:

=== "Do"
    ```php
    $file = Sequence::of('a', 'b', 'c', 'etc...')
        ->toIdentity()
        ->map(Content::ofChunks(...))
        ->map(static fn($content) => File::named('foo', $content))
        ->unwrap();
    ```

=== "Instead of..."
    ```php
    $file = File::named(
        'foo',
        Content::ofChunks(
            Sequence::of('a', 'b', 'c', 'etc...'),
        ),
    );
    ```

??? note
    Here `Content` and `File` are imaginary classes, but you can find equivalent classes in [`innmind/filesystem`](https://packagist.org/packages/innmind/filesystem).

??? tip
    The [`Identity`](identity.md) returned carries the lazyness of the sequence. This allows composing sequences without having to be aware if the source is lazy or not.

    === "Lazy"
        ```php
        $value = Sequence::lazy(static fn() => yield from \range(0, 100))
            ->toIdentity()
            ->toSequence();
        // does the same as
        $value = Sequence::lazy(
            static fn() => yield Sequence::lazy(
                static fn() => yield from \range(0, 100),
            ),
        );
        ```

    === "InMemory"
        ```php
        $value = Sequence::of(...\range(0, 100))
            ->toIdentity()
            ->toSequence();
        // does the same as
        $value = Sequence::of(
            Sequence::of(
                ...\range(0, 100),
            ),
        );
        ```

    In both cases thanks to the `Identity` you only need to specify once if the whole thing is lazy or not.

### `->safeguard()`

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

### `->memoize()` :material-memory-arrow-down:

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
