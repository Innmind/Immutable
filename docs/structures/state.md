# `State`

The `State` monad allows you to build a set of pure steps to compute a new state. Since the initial state is given when all the steps are built it means that all steps are lazy, this use function composition (so everything is kept in memory).

The state and value can be of any type.

??? warning "Deprecated"
    `State` is deprecated and will be removed in the next major release.

## `::of()`

```php
use Innmind\Immutable\{
    State,
    State\Result,
};

/** @var State<array, int> */
$state = State::of(function(array $logs) {
    return Result::of($logs, 0);
});
```

## `->map()`

This method will modify the value without affecting the currently held state.

```php
use Innmind\Immutable\{
    State,
    State\Result,
};

/** @var State<array, int> */
$state = State::of(function(array $logs) {
    return Result::of($logs, 0);
});

$state = $state->map(fn($value) => $value + 1);
```

## `->flatMap()`

This method allows you to modify both state and values.

```php
use Innmind\Immutable\{
    State,
    State\Result,
};

/** @var State<array, int> */
$state = State::of(function(array $logs) {
    return Result::of($logs, 0);
});

$state = $state->flatMap(fn($value) => State::of(function(array $logs) use ($value) {
    $value++;

    return Result::of(
        \array_merge($logs, "The new value is $value"),
        $value,
    );
}));
```

## `->run()`

This is the only place where you can run the steps to compute the new state.

```php
use Innmind\Immutable\{
    State,
    State\Result,
};

/** @var State<array, int> */
$result = State::of(function(array $logs) {
    return Result::of($logs, 0);
})
    ->map(fn($value) => $value + 1)
    ->flatMap(fn($value) => State::of(function(array $logs) use ($value) {
        $value++;

        return Result::of(
            \array_merge($logs, "The new value is $value"),
            $value,
        );
    }))
    ->run([]);

$result->state(); // ['The new value is 2']
$result->value(); // 2
```
