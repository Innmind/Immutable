# `Either`

This structure represent an alternative between 2 possibles types `L` and `R` (left and right), with a bias toward the right value. Usually this is used to represent `Either<SomeError, ValidValue>`, the valid value being on the right side benefit the right bias as `map`, `flatMap` and `filter` are done on the right value.

This technique is borrowed from the functional programming world.

For the examples below we will use the given imaginary functions:

```php
use Innmind\Immutable\Either;

/**
 * @return Either<Error, User>
 */
function identify(ServerRequest $request): Either {
    if (/* some condition */) {
        return Either::right($theUser);
    }

    return Either::left(new Error('User not found'));
}

/**
 * @return Either<Error, Resource>
 */
function accessResource(User $user): Either {
    if (/* check if user is allowed */) {
        return Either::right($resource);
    }

    return Either::left(new Error('User is not allowed'));
}
```

!!! note ""
    `ServerRequest`, `User`, `Resource` and `Error` are imaginary classes.

## `::left()`

This builds an `Either` instance with the given value in the left hand side.

```php
$either = Either::left($anyValue);
```

!!! note ""
    Usually this side is used for errors.

## `::right()`

This builds an `Either` instance with the given value in the right hand side.

```php
$either = Either::right($anyValue);
```

!!! note ""
    Usually this side is used for valid values.

## `::defer()`

This is used to return an `Either` early with known data type but with the value that will be extracted from the callable when calling `->match()`. The main use case is for IO operations.

```php
$either = Either::defer(static function() {
    try {
        $value = /* wait for some IO operation like an http call */;

        return Either::right($value);
    } catch (\Throwable $e) {
        return Either::left($e);
    }
});
```

Methods called (except `match`) on a deferred `Either` will not be called immediately but will be composed to be executed once you call `match`.

!!! warning ""
    This means that if you never call `match` on a deferred `Either` it will do nothing.

## `->map()`

This will apply the map transformation on the right value if there is one, otherwise it's only a type change.

```php
/** @var Either<Error, User> */
$either = identify($serverRequest);
/** @var Either<Error, Impersonated> */
$impersonated = $either->map(
    fn(User $user): Impersonated => $user->impersonateAdmin(),
);
```

## `->flatMap()`

This is similar to `->map()` but instead of returning the new right value you return a new `Either` object.

```php
/** @var Either<Error, User> */
$either = identify($serverRequest);
/** @var Either<Error, Resource> */
$resource = $either->flatMap(fn(User $user): Either => accessResource($user));
```

## `->match()`

This is the only way to extract the wrapped value.

```php
/** @var Response */
$response = identify($serverRequest)
    ->flatMap(fn(User $user): Either => accessResource($user))
    ->match(
        fn(Resource $resource) => new Response(200, $resource->toString()),
        fn(Error $error) => new Response(400, $error->message()), //(1)
    );
```

1. Here the error can be from `identify` or from `accessResource`.

!!! note ""
    `Response` is an imaginary class.

## `->otherwise()`

This is like `->flatMap()` but is called when the instance contains a left value. The callable must return a new `Either` object.

```php
/**
 * @return Either<Error, User>
 */
function identifyViaJsonPayload(ServerRequest $request): Either {
    if (/* find user from json payload */) {
        return Either::right($theUser);
    }

    return Either::left(new Error('User not found'));
}

/** @var Either<Error, User> */
$either = identify($request)
    ->otherwise(fn() => identifyViaJsonPayload($request));
```

## `->filter()`

Use this method to validate the right value when there is one. If the predicate doesn't accept the right value then it will return the value from the second callable as a left value.

```php
identify($request)
    ->filter(
        fn(User $user): bool => $user->isAllowed(),
        fn() => new Error('User is not allowed'),
    )
    ->match(
        fn(User $user) => doSomething($user), //(1)
        fn(Error $error) => print($error->message()), //(2)
    );
```

1. Here we know the user is allowed.
2. Can be "User not found" or "User is not allowed".

## `->leftMap()`

This is similar to the `->map()` function but will be applied on the left value only.

```php
/** @var Either<ErrorResponse, User> */
$either = identify($request)
    ->leftMap(fn(Error $error) => new ErrorResponse($error));
```

## `->maybe()`

This returns a [`Maybe`](maybe.md) containing the right value, in case of a left value it returns a `Maybe` with nothing inside.

```php
Either::right('something')->maybe()->match(
    static fn($value) => $value,
    static fn() => null,
); // returns 'something'
Either::left('something')->maybe()->match(
    static fn($value) => $value,
    static fn() => null,
); // returns null
```

## `->memoize()`

This method force to load the contained value into memory. This is only useful for a deferred `Either`, this will do nothing for other either as the value is already known.

```php
Either::defer(function() {
    return Either::right(\rand());
})
    ->map(static fn($i) => $i * 2) // value still not loaded here
    ->memoize() // call the rand function and then apply the map and store it in memory
    ->match(
        static fn($i) => doStuff($i),
        static fn() => null,
    );
```

## `->flip()`

This method changes the side of the value contained in the `Either`. This is useful when you want to only keep the error and discard the right value you would use like this:

```php
/**
 * @return Either<SomeError, SomeData>
 */
function foo() { /*...*/}

$error = foo() // returns type Either<SomeError, SomeData>
    ->flip() // returns type Either<SomeData, SomeError>
    ->maybe(); // returns type Maybe<SomeError>
```

## `->eitherWay()`

This method is kind of combines both `flatMap` and `otherwise` in a single call. This is useful when you can't call `otherwise` after `flatMap` because you don't want to override the left value returned by `flatMap`.

```php
/**
 * @return Either<SomeError, SideEffect> SideEffect when on macOS
 */
function isMac(): Either { /* ... */}
/**
 * @return Either<SomeError, SideEffect>
 */
function runMac(): Either { /* ... */ }
/**
 * @return Either<SomeError, SideEffect>
 */
function runLinux(): Either { /* ... */ }

$_ = isMac()->eitherWay(runMac(...), runLinux(...));
```

In this case we want to run `runLinux` only when `isMac` returns a `SideEffect` which is possible thanks to `->eitherWay()`. Contrary to `isMac()->flatMap(runMac(...))->otherwise(runLinux(...))` that could lead to `runLinux` to be called if `runMac` returns an error.
