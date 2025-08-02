<?php
declare(strict_types = 1);

use Innmind\Immutable\Validation;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Validation::match()',
        given(Set::type()),
        static function($assert, $value) {
            $assert->same(
                $value,
                Validation::success($value)->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(
                [$value],
                Validation::fail($value)->match(
                    static fn() => null,
                    static fn($value) => $value->toList(),
                ),
            );
        },
    );

    yield proof(
        'Validation::map()',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $initial, $new) {
            $assert->same(
                [$initial, $new],
                Validation::success($initial)
                    ->map(static fn($value) => [$value, $new])
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->null(
                Validation::fail($initial)
                    ->map(static fn($value) => [$value, $new])
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
        },
    );

    yield proof(
        'Validation::flatMap()',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $initial, $new) {
            $assert->same(
                [$initial, $new],
                Validation::success($initial)
                    ->flatMap(static fn($value) => Validation::success([$value, $new]))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                [[$initial, $new]],
                Validation::success($initial)
                    ->flatMap(static fn($value) => Validation::fail([$value, $new]))
                    ->match(
                        static fn() => null,
                        static fn($value) => $value->toList(),
                    ),
            );
            $assert->null(
                Validation::fail($initial)
                    ->flatMap(static fn($value) => Validation::success([$value, $new]))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
        },
    );

    yield proof(
        'Validation::mapFailures()',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $initial, $new) {
            $assert->same(
                [[$initial, $new]],
                Validation::fail($initial)
                    ->mapFailures(static fn($value) => [$value, $new])
                    ->match(
                        static fn() => null,
                        static fn($value) => $value->toList(),
                    ),
            );
            $assert->null(
                Validation::success($initial)
                    ->mapFailures(static fn($value) => [$value, $new])
                    ->match(
                        static fn() => null,
                        static fn($value) => $value->toList(),
                    ),
            );
        },
    );

    yield proof(
        'Validation::otherwise()',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $initial, $new) {
            $assert->null(
                Validation::success($initial)
                    ->otherwise(static fn($value) => Validation::success([$value, $new]))
                    ->match(
                        static fn() => null,
                        static fn($value) => $value->toList(),
                    ),
            );
            $assert->same(
                [[[$initial], $new]],
                Validation::fail($initial)
                    ->otherwise(static fn($value) => Validation::fail([$value->toList(), $new]))
                    ->match(
                        static fn() => null,
                        static fn($value) => $value->toList(),
                    ),
            );
            $assert->same(
                [[$initial], $new],
                Validation::fail($initial)
                    ->otherwise(static fn($value) => Validation::success([$value->toList(), $new]))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
        },
    );

    yield proof(
        'Validation::maybe()',
        given(Set::type()),
        static function($assert, $value) {
            $assert->same(
                $value,
                Validation::success($value)
                    ->maybe()
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->null(
                Validation::fail($value)
                    ->maybe()
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
        },
    );

    yield proof(
        'Validation::either()',
        given(Set::type()),
        static function($assert, $value) {
            $assert->same(
                $value,
                Validation::success($value)
                    ->either()
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                [$value],
                Validation::fail($value)
                    ->either()
                    ->match(
                        static fn() => null,
                        static fn($value) => $value->toList(),
                    ),
            );
        },
    );

    yield proof(
        'Validation::and()',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $a, $b) {
            $success = Validation::success($a);
            $fail = Validation::fail($b);

            $assert->same(
                [$a, $a],
                $success
                    ->and(
                        $success,
                        static fn($a, $b) => [$a, $b],
                    )
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                [$b],
                $success
                    ->and(
                        $fail,
                        static fn($a, $b) => [$a, $b],
                    )
                    ->match(
                        static fn($value) => $value,
                        static fn($value) => $value->toList(),
                    ),
            );
            $assert->same(
                [$b],
                $fail
                    ->and(
                        $success,
                        static fn($a, $b) => [$a, $b],
                    )
                    ->match(
                        static fn($value) => $value,
                        static fn($value) => $value->toList(),
                    ),
            );
            $assert->same(
                [$b, $b],
                $fail
                    ->and(
                        $fail,
                        static fn($a, $b) => [$a, $b],
                    )
                    ->match(
                        static fn($value) => $value,
                        static fn($value) => $value->toList(),
                    ),
            );
        },
    );
};
