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

**Important**: this structure is immutable and is only typed with [Psalm](http://psalm.dev) to keep the interface simple and jump between types more easily. So make sure to use Psalm in your code so you know if you use the structure correctly.

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

Both functions used with `->match()` **must** have the same return type. Otherwise you would have to check the type of the returned value to know what to do next, this would break the logic of this approach that frees you from writing any `if` statement.

## `->otherwise()`

This is like `->flatMap()` but is called when there is no value wrapped. The other difference from `->flatMap()` is that you must return the same wrapped value type.

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
