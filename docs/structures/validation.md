# `Validation`

This structure is similar to [`Either`](either.md) except that the right side is called success and left fail. The difference is that `Validation` allows to accumulate failures.

For the examples below we will use the given imaginary functions:

```php
use Innmind\Immutable\Validation;

/**
 * @return Validation<Error, string>
 */
function isEmail(string $value): Validation {
    if (\filter_var($value, \FILTER_VALIDATE_EMAIL)) {
        return Validation::success($value);
    }

    return Validation::fail(new Error("$value is not an email"));
}

/**
 * @return Validation<Error, string>
 */
function isLocal(string $value): Validation {
    if (\str_ends_with($value, '.local')) {
        return Validation::success($value);
    }

    return Validation::fail(new Error('Not a local email'));
}
```

!!! note ""
    `Error` is imaginary class.

## `::fail()`

This builds a `Validation` instance with the given value in the fail side.

```php
$validation = Validation::fail($anyValue);
```

## `::success()`

This builds a `Validation` instance with the given value in the success side.

```php
$validation = Validation::success($anyValue);
```

## `->map()`

This will apply the map transformation on the success value if there is one, otherwise it's only a type change.

```php
/** @var Validation<Error, string> */
$validation = isEmail('foo@example.com');
/** @var Either<Error, Email> */
$email = $validation->map(fn(string $email): Email => new Email($email));
```

## `->flatMap()`

This is similar to `->map()` but instead of returning the new success value you return a new `Validation` object.

```php
/** @var Validation<Error, string> */
$validation = isEmail('foo@example.com');
/** @var Validation<Error, string> */
$localEmail = $either->flatMap(fn(string $email): Validation => isLocal($email));
```

## `->guard()`

This behaves like [`->flatMap()`](#-flatmap) except any failure contained in the validation returned by the callable won't be recovered when calling [`->xotherwise()`](#-xotherwise).

## `->match()`

This is the only way to extract the wrapped value.

```php
/** @var Email */
$localEmail = isEmail($serverRequest)
    ->flatMap(fn(string $email): Validation => isLocal($email))
    ->map(static fn(string $email) => new Email($email))
    ->match(
        fn(Email $email) => $email,
        fn(Sequence $failures) => throw new \Exception(
            \implode(', ', $failure->toList()),
        ),
    );
```

## `->otherwise()`

This is like `->flatMap()` but is called when the instance contains failures. The callable must return a new `Validation` object.

```php
/** @var Validation<Error, string> */
$email = isEmail('invalid value')
    ->otherwise(fn() => isEmail('foo@example.com'));
```

## `->xotherwise()`

This behaves like [`->otherwise()`](#-otherwise) except when conjointly used with [`->guard()`](#-guard). Guarded failures can't be recovered.

An example of this problem is an HTTP router with 2 validations. One tries to validate it's a `POST` request, then validates the request body, the other tries to validate a `GET` request. It would look something like this:

```php
$result = validatePost($request)
    ->flatMap(static fn() => validateBody($request))
    ->otherwise(static fn() => validateGet($request));
```

The problem here is that if the request is indeed a `POST` we try to validate the body. But if the latter fails then we try to validate it's a `GET` query. In this case the failure would indicate the request is not a `GET`, which doesn't make sense.

The correct approach is:

```php
$result = validatePost($request)
    ->guard(static fn() => validateBody($request))
    ->xotherwise(static fn() => validateGet($request));
```

This way if the body validation fails it will return this failure and not that it's not a `GET`.

## `->mapFailures()`

This is similar to the `->map()` function but will be applied on each failure.

```php
/** @var Either<Exception, string> */
$email = isEmail('foo@example.com')
    ->mapFailures(fn(Error $error) => new \Exception($error->toString()));
```

## `->and()`

This method allows to aggregate the success values of 2 `Validation` objects or aggregates the failures if at least one of them is a failure.

```php
$foo = isEmail('foo@example.com');
$bar = isEmail('bar@example.com');
$baz = isEmail('invalid value');
$foobar = isEmail('another value');

$foo
    ->and(
        $bar,
        static fn($a, $b) => [$a, $b],
    )
    ->match(
        static fn($value) => $value,
        static fn() => null,
    ); // returns ['foo@example.com', 'bar@example.com']
$foo
    ->and(
        $baz,
        static fn($a, $b) => [$a, $b],
    )
    ->match(
        static fn() => null,
        static fn($failures) => $failures->toList(),
    ); // returns [new Error('invalid value is not an email')]
$foobar
    ->and(
        $baz,
        static fn($a, $b) => [$a, $b],
    )
    ->match(
        static fn() => null,
        static fn($failures) => $failures->toList(),
    ); // returns [new Error('another value is not an email'), new Error('invalid value is not an email')]
```

## `->maybe()`

This returns a [`Maybe`](maybe.md) containing the success value, in case of failures it returns a `Maybe` with nothing inside.

```php
Validation::success('something')->maybe()->match(
    static fn($value) => $value,
    static fn() => null,
); // returns 'something'
Validation::fail('something')->maybe()->match(
    static fn($value) => $value,
    static fn() => null,
); // returns null
```

## `->either()`

This returns an [`Either`](either.md) containing the success value as the right side, in case of failures it returns an `Either` with failures as the left side.

```php
Validation::success('something')->either()->match(
    static fn($value) => $value,
    static fn() => null,
); // returns 'something'
Validation::fail('something')->either()->match(
    static fn() => null,
    static fn($value) => $value,
); // returns Sequence<string>
```
