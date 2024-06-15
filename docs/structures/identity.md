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
