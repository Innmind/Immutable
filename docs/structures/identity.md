# `Identity`

This is the simplest monad there is. It's a simple wrapper around a value to allow chaining calls on this value.

Let's say you have a string you want to camelize, here's how you'd do it:

=== "Identity"
    ```php
    $value = Identity::of('some value to camelize')
        ->map(fn($string) => \explode(' ', $string))
        ->map(fn($parts) => \array_map(
            \ucfirst(...),
            $parts,
        ))
        ->map(fn($parts) => \implode('', $parts))
        ->map(\lcfirst(...))
        ->unwrap();

    echo $value; // outputs "someValueToCamelize"
    ```

=== "Imperative"
    ```php
    $string = 'some value to camelize';
    $parts = \explode(' ', $string);
    $parts = \array_map(
        \ucfirst(...),
        $parts,
    );
    $string = \implode('', $parts);
    $value = \lcfirst($string);

    echo $value; // outputs "someValueToCamelize"
    ```

=== "Pyramid of doom"
    ```php
    $value = \lcfirst(
        \implode(
            '',
            \array_map(
                \ucfirst(...),
                \explode(
                    ' ',
                    'some value to camelize',
                ),
            ),
        ),
    );

    echo $value; // outputs "someValueToCamelize"
    ```

!!! abstract ""
    In the end this monad does not provide any behaviour, it's a different way to write and read your code.

## Lazy computation

By default the `Identity` apply each transformation when `map` or `flatMap` is called. But you can defer the application of the transformations to when the `unwrap` method is called. This can be useful when you're not sure the computed value will be really used.

Instead of using `Identity::of()` you'd do:

```php
$value = Identity::defer(static fn() => 'some value'); //(1)
// or
$value = Identity::lazy(static fn() => 'some value');
// then you can use the identity as before (see above)
```

1. Here the value is a string but you can use whatever type you want.

The difference between `lazy` and `defer` is that the first one will recompute the underlying value each time the `unwrap` method is called while the other one will compute it once and then always return the same value.

## Wrapping the underlying value in a `Sequence`

This monad has a `toSequence` method that will create a new [`Sequence`](sequence.md) containing the underlying value.

Both examples do the same:

=== "Declarative"
    ```php
    $value = Identity::of('some value')
        ->toSequence();
    ```

=== "Imperative"
    ```php
    $value = Sequence::of('some value');
    ```

On the surface this seems to not be very useful, but it becomes interesting when the identity is lazy or deferred. The laziness is propagated to the sequence.

Both examples do the same:

=== "Declarative"
    ```php
    $value = Identity::lazy(static fn() => 'some value')
        ->toSequence();
    ```

=== "Imperative"
    ```php
    $value = Sequence::lazy(static fn() => yield 'some value');
    ```

This combined to the [`Sequence::toIdentity()`](sequence.md#-toidentity) allows you to chain and compose sequences without having to be aware if the source sequence is lazy or not.
