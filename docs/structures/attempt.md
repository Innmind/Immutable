# `Attempt`

This structures is similar to [`Either`](either.md) but where the left value is necessarily an instance of `\Throwable`.

Its main use is as a return type of any function that would normally throw an exception. Instead of throwing and let the exception bubble up the call stack, it's caught in the structure and forces you to deal with this exception at some point.

Unlike an `Either` the error type can't be more precise than `\Throwable`.

`Attempt` is intended to be used as a return type where the call may fail but you can't know in advance all the possible failing scenarii. This is the case for interfaces where the kind of error will depend on the implementation details.

If you already know all the possible failing scenarii you should use an `Either` instead.

??? note
    In other languages this monad is called `Try`. But this is a reserved keyword in PHP, hence the name `Attempt`.

## `::error()`

This builds an `Attempt` that failed with the given exception:

```php
$attempt = Attempt::error(new \Exception);
```

!!! note ""
    You will rarely use this method directly.

## `::result()`

This builds an `Attempt` that succeeded with the given value:

```php
$attempt = Attempt::result($anyValue);
```

!!! note ""
    You will rarely use this method directly.

## `::of()`

This builds an `Attempt` that will immediately call the callable and catch any exception:

```php
$attempt = Attempt::of(static function() {
    if (/* some condition */) {
        throw new \Exception;
    }

    return $anyValue;
});
```

This is the equivalent of:

```php
$doStuff = static function() {
    if (/* some condition */) {
        return Attempt::error(new \Exception);
    }

    return Attempt::result($anyValue);
};
$attempt = $doStuff();
```

!!! success ""
    This is very useful to wrap any third party code to a monadic style.

## `::defer()`

This builds an `Attempt` where the callable passed will be called only when [`->memoize()`](#-memoize) or [`->match()`](#-match) is called.

```php
$attempt = Attempt::defer(static fn() => Attempt::of(doStuff(...)));
// doStuff has not been called yet
$attempt->memoize();
// doStuff has been called
```

The main use case is for IO operations.

## `->map()`

This will apply the map transformation on the result if no previous error occured.

=== "Result"
    ```php
    $attempt = Attempt::of(static fn() => 1/2)
        ->map(static fn(int $i) => $i*2);
    ```

    Here `#!php $attempt` contains `1`;

=== "Error"
    ```php
    $attempt = Attempt::of(static fn() => 1/0)
        ->map(static fn(int $i) => $i*2);
    ```

    Here `#!php $attempt` contains a `DivisionByZeroError` and the callable passed to `map` has not been called.

## `->flatMap()`

This is similar to `#!php ->map()` except the callable passed to it must return an `Attempt` indicating that it may fail.

```php
$attempt = Attempt::result(2 - $reduction)
    ->flatMap(static fn(int $divisor) => Attempt::of(
        static fn() => 42 / $divisor,
    ));
```

If `#!php $reduction` is `#!php 2` then `#!php $attempt` will contain a `DivisionByZeroError` otherwise for any other value it will contain a fraction of `#!php 42`.

## `->match()`

This extracts the result value but also forces you to deal with any potential error.

```php
$result = Attempt::of(static fn() => 2 / $reduction)->match(
    static fn($fraction) => $fraction,
    static fn(\Throwable $e) => $e,
);
```

If `#!php $reduction` is `#!php 0` then `#!php $result` will be an instance of `DivisionByZeroError`, otherwise it will be a fraction of `#!php 2`.

## `->recover()`

This will allow you to recover in case of a previous error.

```php
$attempt = Attempt::of(static fn() => 1/0)
    ->recover(static fn(\Throwable $e) => Attempt::result(42));
```

Here `#!php $attempt` is `#!php 42` because the first `Attempt` raised a `DivisionByZeroError`.

## `->maybe()`

This converts an `Attempt` to a `Maybe`.

=== "Result"
    ```php
    Attempt::result($value)->maybe();
    // is the same as
    Maybe::just($value);
    ```

=== "Error"
    ```php
    Attempt::error(new \Exception)->maybe()
    // is the same as
    Maybe::nothing();
    ```

## `->either()`

This converts an `Attempt` to a `Either`.

=== "Result"
    ```php
    Attempt::result($value)->either();
    // is the same as
    Either::right($value);
    ```

=== "Error"
    ```php
    Attempt::error(new \Exception)->either()
    // is the same as
    Either::left(new \Exception);
    ```

## `->memoize()`

This method force to load the contained value into memory. This is only useful for a deferred `Attempt`, this will do nothing for other attempts as the value is already known.

```php
Attempt::defer(static fn() => Attempt::result(\rand()))
    ->map(static fn($i) => $i * 2) // value still not loaded here
    ->memoize() // call the rand function and then apply the map and store it in memory
    ->match(
        static fn($i) => doStuff($i),
        static fn() => null,
    );
```
