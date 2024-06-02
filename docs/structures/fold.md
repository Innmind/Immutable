# `Fold`

The `Fold` monad is intented to work with _(infinite) stream of data_ by folding each element to a single value. This monad distinguishes between the type used to fold and the result type, this allows to inform the _stream_ that it's no longer necessary to extract elements as the folding is done.

An example is reading from a socket as it's an infinite stream of strings:

```php
$socket = \stream_socket_client(/* args */);
/** @var Fold<string, list<string>, list<string>> */
$fold = Fold::with([]);

do {
    // production code should wait for the socket to be "ready"
    $line = \fgets($socket);

    if ($line === false) {
        $fold = Fold::fail('socket not readable');
    }

    $fold = $fold
        ->map(static fn($lines) => \array_merge($lines, [$line]))
        ->flatMap(static fn($lines) => match (\end($lines)) {
            "quit\n" => Fold::result($lines),
            default => Fold::with($lines),
        });
    $continue = $fold->match(
        static fn() => true, // still folding
        static fn() => false, // got a result so stop
        static fn() => false, // got a failure so stop
    );
} while ($continue);

$fold->match(
    static fn() => null, // unreachable in this case because no more folding outside the loop
    static fn($lines) => \var_dump($lines),
    static fn($failure) => throw new \RuntimeException($failure),
);
```

This example will read all lines from the socket until one line contains `quit\n` then the loop will stop and either dump all the lines to the output or `throw new RuntimeException('socket not reachable')`.

## `::with()`

This named constructor accepts a value with the notion that more elements are necessary to compute a result

## `::result()`

This named constructor accepts a _result_ value meaning that folding is finished.

## `::fail()`

This named constructor accepts a _failure_ value meaning that the folding operation failed and no _result_ will be reachable.

## `->map()`

This method allows to transform the value being folded.

```php
$fold = Fold::with([])->map(static fn(array $folding) => new \ArrayObject($folding));
```

## `->flatMap()`

This method allows to both change the value and the _state_, for example switching from _folding_ to _result_.

```php
$someElement = /* some data */;
$fold = Fold::with([])->flatMap(static fn($elements) => match ($someElement) {
    'finish' => Fold::result($elements),
    default => Fold::with(\array_merge($elements, [$someElement])),
});
```

## `->mapResult()`

Same as [`->map()`](#map) except that it will transform the _result_ value when there is one.

## `->mapFailure()`

Same as [`->map()`](#map) except that it will transform the _failure_ value when there is one.

## `->maybe()`

This will return the _terminal_ value of the folding, meaning either a _result_ or a _failure_.

```php
Fold::with([])->maybe()->match(
    static fn() => null, // not called as still folding
    static fn() => doStuff(), // called as it is still folding
);
Fold::result([])->maybe()->match(
    static fn($either) => $either->match(
        static fn($result) => $result, // the value here is the array passed to ::result() above
        static fn() => null, // not called as it doesn't contain a failure
    ),
    static fn() => null, // not called as we have a result
);
Fold::fail('some error')->maybe()->match(
    static fn($either) => $either->match(
        static fn() => null, // not called as we have a failure
        static fn($error) => var_dump($error), // the value here is the string passed to ::fail() above
    ),
    static fn() => null, // not called as we have a result
);
```

## `->match()`

This method allows to extract the value contained in the object.

```php
Fold::with([])->match(
    static fn($folding) => doStuf($folding), // value from ::with()
    static fn() => null, // not called
    static fn() => null, // not called
);
Fold::result([])->match(
    static fn() => null, // not called
    static fn($result) => doStuf($result), // value from ::result()
    static fn() => null, // not called
);
Fold::fail('some error')->match(
    static fn() => null, // not called
    static fn() => null, // not called
    static fn($error) => doStuf($error), // value from ::fail()
);
```
