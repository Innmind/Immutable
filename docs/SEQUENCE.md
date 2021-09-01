# `Sequence`

A sequence is an ordered list of elements, think of it like an array such as `[1, 'a', new stdClass]` or a `list<T>` in the [Psalm](http://psalm.dev) nomenclature.

## `::of()`

The `of` static method allows you to create a new sequence of the given [type](types.html):

```php
use Innmind\Immutable\Sequence;

/** @var Sequence<int> */
$sequence = Sequence::of('int');
```

This named constructor also allows you to directly add elements when initialising the sequence:

```php
Sequence::of('int', 1, 2, 3, $etc);
```

## `::defer()`

This named constructor is for advanced use cases where you want the data of your sequence to be loaded upon use only and not initialisation.

An example for such a use case is a sequence of log lines coming from a file:

```php
$sequence = Sequence::defer('string', (function() {
    yield from readSomeFile('apache.log');
})());
```

The method as always ask the type of the elements and a generator that will provide the elements. Once the elements are loaded they are kept in memory so you can run multiple operations on it without loading the file twice.

**Important**: beware of the case where the source you read the elements is not altered before the first use of the sequence.

## `::lazy()`

This is similar to `::defer()` with the exception that the elements are not kept in memory but reloaded upon each use.

```php
$sequence = Sequence::lazy('string', function() {
    yield from readSomeFile('apache.log');
});
```

**Important**: since the elements are reloaded each time the immutability responsability is up to you because the source may change or if you generate objects it will generate new objects each time (so if you make strict comparison it will fail).

## `::mixed()`

This is a shortcut for `::of('mixed', ...$mixed)`.

## `::ints()`

This is a shortcut for `::of('int', int ...$ints)`.

## `::floats()`

This is a shortcut for `::of('float', float ...$floats)`.

## `::strings()`

This is a shortcut for `::of('string', string ...$strings)`.

## `::objects()`

This is a shortcut for `::of('object', object ...$objects)`.

## `->isOfType()`

This method is here to help you know the sequence is of a certain type:

```php
$sequence = Sequence::of('stdClass');
$sequence->isOfType('int'); // false
$sequence->isOfType('stdClass'); // true
```

## `->type()`

This returns the type you specified at initialisation.

```php
$sequence = Sequence::of('stdClass');
$sequence->type(); // 'stdClass'
```

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

This method will return the element at the given index in the sequence. If the index doesn't exist it will throw an exception.

```php
$sequence = Sequence::ints(1, 4, 6);
$sequence->get(1); // 4
$sequence->get(3); // throws Innmind\Immutable\Exception\OutOfBoundException
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
Sequence::ints()->equals(Sequence::strings()); // throws \TypeError
```

## `->filter()`

Removes elements from the sequence that don't match the given predicate.

```php
$sequence = Sequence::ints(1, 2, 3, 4)->filter(fn($i) => $i % 2 === 0);
$sequence->equals(Sequence::ints(2, 4));
```

## `->foreach()`

Use this method to call a function for each element of the sequence. Since this method doesn't return anything it is the only place acceptable to create side effects.

```php
Sequence::strings('hello', 'world')->foreach(function(string $string): void {
    echo $string.' ';
});
```

## `->group()`

This will create multiples sequences with elements regrouped under the same key computed by the given function.

```php
$urls = Sequence::strings(
    'http://example.com',
    'http://example.com/foo',
    'https://example.com',
    'ftp://example.com',
);
/** @var Innmind\Immutable\Map<string, Sequence<string>> */
$map = $urls->group(
    'string',
    fn(string $url): string => \parse_url($url)['scheme'],
);
$map->get('http')->equals(Sequence::strings('http://example.com', 'http://example.com/foo')); // true
$map->get('https')->equals(Sequence::strings('https://example.com')); // true
$map->get('ftp')->equals(Sequence::strings('ftp://example.com')); // true
```

## `->groupBy()`

This is similar to the `->group()` method with the exception that the key type of the returned [`Map`](map.html) will be determined by the first computed key value.

Since the key type is computed you cannot call `->groupBy()` on an empty sequence, otherwise it will throw `Innmind\Immutable\Exception\CannotGroupEmptyStructure`.

```php
$urls = Sequence::strings(
    'http://example.com',
    'http://example.com/foo',
    'https://example.com',
    'ftp://example.com',
);
/** @var Innmind\Immutable\Map<string, Sequence<string>> */
$map = $urls->groupBy(fn(string $url): string => \parse_url($url)['scheme']);
$map->get('http')->equals(Sequence::strings('http://example.com', 'http://example.com/foo')); // true
$map->get('https')->equals(Sequence::strings('https://example.com')); // true
$map->get('ftp')->equals(Sequence::strings('ftp://example.com')); // true
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
$sequence->contains('42'); // throws \TypeError
```

## `->indexOf()`

This will return the index number at which the first occurence of the element was found.

```php
$sequence = Sequence::ints(1, 2, 3, 2);
$sequence->indexOf(2); // 1
$sequence->indexOf(4); // throws Innmind\Immutable\Exception\ElementNotFound
```

## `->indices()`

Create a new sequence of integers representing the indices of the original sequence.

```php
$sequence = Sequence::ints(1, 2, 3);
$sequence->indices()->equals(Sequence::ints(...\range(0, $sequence->size() - 1)));
```

## `->map()`

Create a new sequence of the same type with the exact same number of elements but modified by the given function.

```php
$ints = Sequence::ints(1, 2, 3);
$squares = $ints->map(fn($i) => $i**2);
$squares->equals(Sequence::ints(1, 4, 9)); // true
```

## `->mapTo()`

This is similar to `->map()` except you can change the type of the generated sequence.

```php
$ints = Sequence::ints(1, 2, 3);
$squares = $ints->mapTo('string', fn($i) => (string) ($i**2));
$squares->equals(Sequence::strings('1', '4', '9')); // true
```

## `->pad()`

Add the same element to a new sequence in order that its size is at least the given one.

```php
$sequence = Sequence::ints(1, 2, 3);
$sequence->pad(2, 0)->equals(Sequence::ints(1, 2, 3)); // true
$sequence->pad(5, 0)->equals(Sequence::ints(1, 2, 3, 0, 0)); // true
```

## `->partition()`

This method is similar to `->group()` method but the map keys are always booleans. The difference is that here the 2 keys are always present whereas with `->group()` it will depend on the original sequence.

```php
$sequence = Sequence::ints(1, 2, 3);
/** @var Map<bool, Sequence<int>> */
$map = $sequence->partition(fn($int) => $int % 2 === 0);
$map->get(true)->equals(Sequence::ints(2)); // true
$map->get(false)->equals(Sequence::ints(1, 3)); // true
```

## `->slice()`

Return a new sequence with only the elements that were between the given indices. (The upper bound is not included)

```php
$sequence = Sequence::ints(4, 3, 2, 1);
$sequence->slice(1, 4)->equals(Sequence::ints(3, 2)); // true
```

## `->splitAt()`

Create a new sequence of 2 sequences split at the given index.

```php
$sequence = Sequence::ints(4, 3, 1, 0);
/** @var Sequence<Sequence<int>> */
$splits = $sequence->splitAt(2);
$splits->size(); // 2
$splits->get(0)->equals(Sequence::ints(4, 3)); // true
$splits->get(1)->equals(Sequence::ints(1, 0)); // true
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

## `->reduce()`

Iteratively compute a value for all the elements in the sequence.

```php
$sequence = Sequence::ints(1, 2, 3, 4);
$sum = $sequence->reduce(0, fn($sum, $int) => $sum + $int);
$sum; // 10
```

## `->clear()`

Create an empty new sequence of the same type.

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

## `->toSequenceOf()`

This is similar to `->mapTo()` but you can yield multiple values for one original element.

```php
$sequence = Sequence::ints(1, 2, 3)->toSequenceOf(
    'int|string',
    function(int $int) {
        yield $int;
        yield (string) $int;
    },
);
$sequence->equals(Sequence::of('int|string', 1, '1', 2, '2', 3, '3')); // true
```

## `->toSetOf()`

Similar to `->toSequenceOf()` but it returns a [`Set`](set.html) instead.

```php
$set = Sequence::ints(1, 2, 3)->toSetOf(
    'int|string',
    function(int $int) {
        yield $int;
        yield (string) $int;
    },
);
$set->equals(Set::of('int|string', 1, '1', 2, '2', 3, '3')); // true
```

## `->toMapOf()`

Similar to `->toSequenceOf()` but it returns a [`Map`](map.html) instead.

```php
$map = Sequence::ints(1, 2, 3)->toMapOf(
    'string',
    'int',
    function(int $int) {
        yield (string) $int => $int;
    },
);
$map->equals(
    Map::of('string', 'int')
        ('1', 1)
        ('2', 2)
        ('3', 3)
); // true
```

## `->find()`

Returns the first element that matches the predicate.

```php
$sequence = Sequence::ints(2, 4, 6, 8, 9, 10, 11);
$firstOdd = $sequence->find(fn($i) => $i % 2 === 1);
$firstOdd; // 9
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
