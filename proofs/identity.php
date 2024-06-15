<?php
declare(strict_types = 1);

use Innmind\Immutable\Identity;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Identity::unwrap()',
        given(Set\Type::any()),
        static fn($assert, $value) => $assert->same(
            $value,
            Identity::of($value)->unwrap(),
        ),
    );

    yield proof(
        'Identity::map()',
        given(
            Set\Type::any(),
            Set\Type::any(),
        ),
        static fn($assert, $initial, $expected) => $assert->same(
            $expected,
            Identity::of($initial)
                ->map(static function($value) use ($assert, $initial, $expected) {
                    $assert->same($initial, $value);

                    return $expected;
                })
                ->unwrap(),
        ),
    );

    yield proof(
        'Identity::flatMap()',
        given(
            Set\Type::any(),
            Set\Type::any(),
        ),
        static fn($assert, $initial, $expected) => $assert->same(
            $expected,
            Identity::of($initial)
                ->flatMap(static function($value) use ($assert, $initial, $expected) {
                    $assert->same($initial, $value);

                    return Identity::of($expected);
                })
                ->unwrap(),
        ),
    );

    yield proof(
        'Identity::map() and ::flatMap() interchangeability',
        given(
            Set\Type::any(),
            Set\Type::any(),
            Set\Type::any(),
        ),
        static fn($assert, $initial, $intermediate, $expected) => $assert->same(
            Identity::of($initial)
                ->flatMap(static fn() => Identity::of($intermediate))
                ->map(static fn() => $expected)
                ->unwrap(),
            Identity::of($initial)
                ->flatMap(
                    static fn() => Identity::of($intermediate)->map(
                        static fn() => $expected,
                    ),
                )
                ->unwrap(),
        ),
    );
};
