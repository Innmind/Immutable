# `Maybe`

This type is another approach to represent the possible abscence of a value. Instead of a function returning a result of type `T` or `null` it would return `Maybe<T>`.

This technique is borrowed from the functional programming world.

For the examples below will use the given imaginary function:

```php
use Innmind\Immutable\Maybe;

/** @return Maybe<string> */
function env(string $name): Maybe {
    $value = \getenv($name);

    return $value === false ? Maybe::nothing() : Maybe::just($value);
}
```

## `::just()`

This named constructor tells that there **is** a value that can be used.

## `::nothing()`

This named constructor tells that there is **no** value that can be used.

## `::of()`

This is a shortcut so you don't have to write the `if (is_null)` yourself.

```php
$maybe = \is_null($value) ? Maybe::nothing() : Maybe::just($value);
// is equivalent to
$maybe = Maybe::of($value);
```

## `::all()`

This is a shortcut to make sure all the wrappers contain a value to easily combine them. If any of the arguments doesn't contain a value then the call to `->map()` or `->flatMap()` will return a `Maybe::nothing()`.

```php
$kernel = Maybe::all(env('ENV'), env('DEBUG'))
    ->map(static fn(string $env, string $debug) => new Kernel($env, $debug))
    ->match(
        static fn(Kernel $kernel) => $kernel,
        static fn() => throw new \Exception('ENV or DEBUG environment variable is missing (or both)'),
    );
```

## `::defer()`

This is used to return a `Maybe` early with known data type but with the value that will be extracted from the callable when calling `->match()`. The main use case is for IO operations.

```php
$maybe = Maybe::defer(static function() {
    $value = /* wait for some IO operation like an http call */;

    return Maybe::of($value);
});
```

Methods called (except `match`) on a deferred `Maybe` will not be called immediately but will be composed to be executed once you call `match`.

> **Warning** this means that if you never call `match` on a deferred `Maybe` it will do nothing.

## `->map()`

This function allows you to transform the value into another value that will be wrapped in a `Maybe` object.

```php
$dsn = env('LOGGER_DSN');
/** @var Maybe<array> */
$parsedDsn = $dsn->map(fn(string $dsn): array => \parse_url($dsn));
```

## `->flatMap()`

This is similar to the `->map()` function but instead of returning the value you need to return a wrapped value.

```php
/**
 * This is an imaginary function that would retrieve a User from a database
 * (or elsewhere)
 *
 * @param string $id
 *
 * @return Maybe<User>
 */
function getUser(string $id): Maybe {
    // imaginary implementation
}

$adminId = env('ADMIN_ID');
/** @var Maybe<User> */
$admin = $adminId->flatMap(fn($id) => getUser($id));
```

This allows you to continue chaining calls on `Maybe` instances by juggling with wrapped types.

## `->match()`

This is the only way to extract the wrapped value. But you need to handle both cases where the value exists or where the value doesn't exist.

The example below uses the imaginary `Logger` and `NullLogger` classes.

```php
$dsn = env('LOGGER_DSN');
$logger = $dsn->match(
    static fn(string $url) => new Logger($url),
    static fn() => new NullLogger,
);
```

## `->otherwise()`

This is like `->flatMap()` but is called when there is no value wrapped.

This is useful to create a chain of alternative strategies.

```php
/** @var Maybe<string> */
$dsn = env('DATABASE_LOGGER_DSN')
    ->otherwise(fn() => env('SENTRY_LOGGER_DSN'))
    ->otherwise(fn() => env('FILE_LOGGER_DSN'));
```

This example will first try to retrieve the `DATABASE_LOGGER_DSN`, if it doesn't exist it will try the `SENTRY_LOGGER_DSN`. If the sentry one exists then it will not try to retrieve `FILE_LOGGER_DSN`.

## `->filter()`

When there is a wrapped value it will call the given predicate. If the condition is successful then nothing happens but if it fails it will return a `Maybe::nothing()`.

```php
$dsn = env('LOGGER_DSN');
/** @var Maybe<string> */
$validDsn = $dsn->filter(fn(string $url): bool => \filter_var($url, \FILTER_VALIDATE_URL));
```

`$validDsn` will contain either a valid url or nothing.

## `->keep()`

This is similar to `->filter()` with the advantage of psalm understanding the type in the new `Maybe`.

## `->exclude()`

This is the inverse of the `->filter()` method.

## `->either()`

This returns an [`Either`](EITHER.md) containing the value on the right side and `null` on the left side.

```php
Maybe::just('something')->either()->match(
    static fn($right) => $right,
    static fn() => null,
); // returns 'something'
Maybe::nothing()
    ->either()
    ->leftMap(static fn() => 'something')
    ->match(
        static fn() => null,
        static fn($left) => $left,
    ); // return 'something'
```

## `->memoize()`

This method force to load the contained value into memory. This is only useful for a deferred `Maybe`, this will do nothing for other maybe as the value is already known.

```php
Maybe::defer(function() {
    return Maybe::just(\rand());
})
    ->map(static fn($i) => $i * 2) // value still not loaded here
    ->memoize() // call the rand function and then apply the map and store it in memory
    ->match(
        static fn($i) => doStuff($i),
        static fn() => null,
    );
```

## `->toSequence()`

This method will return a `Sequence` with one or no value. This can be useful when "`flatMap`ping" a `Sequence` like this:

```php
$vars = Sequence::of('DB_URL', 'MAILER_URL', /* and so on */)
    ->flatMap(static fn($var) => env($var)->toSequence());
```

> **Note**
> this example uses the `env` function defined at the start of this documentation.

This is equivalent to:

```php
$vars = Sequence::of('DB_URL', 'MAILER_URL', /* and so on */)
    ->flatMap(static fn($var) => env($var)->match(
        static fn($value) => Sequence::of($value),
        static fn() => Sequence::of(),
    ));
```

## `->eitherWay()`

This method is kind of combines both `flatMap` and `otherwise` in a single call. This is useful when you can't call `otherwise` after `flatMap` because you don't want to override the _nothingness_ returned by `flatMap`.

```php
/**
 * @return Maybe<SideEffect> SideEffect when on macOS
 */
function isMac(): Maybe { /* ... */}
/**
 * @return Maybe<SideEffect>
 */
function runMac(): Maybe { /* ... */ }
/**
 * @return Maybe<SideEffect>
 */
function runLinux(): Maybe { /* ... */ }

$_ = isMac()->eitherWay(runMac(...), runLinux(...));
```

In this case we want to run `runLinux` only when `isMac` returns nothing which is possible thanks to `->eitherWay()`. Contrary to `isMac()->flatMap(runMac(...))->otherwise(runLinux(...))` that could lead to `runLinux` to be called if `runMac` returns nothing.
