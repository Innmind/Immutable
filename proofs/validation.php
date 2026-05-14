<?php
declare(strict_types = 1);

use Innmind\Immutable\Validation;
use Innmind\BlackBox\Set;

return static function($prove) {
    yield $prove
        ->proof('Validation::match()')
        ->given(Set::type())
        ->test(static function($assert, $value) {
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
        });

    yield $prove
        ->proof('Validation::map()')
        ->given(
            Set::type(),
            Set::type(),
        )
        ->test(static function($assert, $initial, $new) {
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
        });

    yield $prove
        ->proof('Validation::flatMap()')
        ->given(
            Set::type(),
            Set::type(),
        )
        ->test(static function($assert, $initial, $new) {
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
        });

    yield $prove
        ->proof('Validation::mapFailures()')
        ->given(
            Set::type(),
            Set::type(),
        )
        ->test(static function($assert, $initial, $new) {
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
        });

    yield $prove
        ->proof('Validation::otherwise()')
        ->given(
            Set::type(),
            Set::type(),
        )
        ->test(static function($assert, $initial, $new) {
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
        });

    yield $prove
        ->proof('Validation::maybe()')
        ->given(Set::type())
        ->test(static function($assert, $value) {
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
        });

    yield $prove
        ->proof('Validation::either()')
        ->given(Set::type())
        ->test(static function($assert, $value) {
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
        });

    yield $prove
        ->proof('Validation::and()')
        ->given(
            Set::type(),
            Set::type(),
        )
        ->test(static function($assert, $a, $b) {
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
        });

    yield $prove
        ->proof('Validation::guard()')
        ->given(
            Set::integers()->above(1),
            Set::integers()->below(-1),
            Set::type(),
        )
        ->test(static function($assert, $positive, $negative, $fail) {
            $assert->same(
                $positive,
                Validation::success($positive)
                    ->guard(static fn() => Validation::success($positive))
                    ->otherwise(static fn() => Validation::success($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );

            $assert->same(
                $negative,
                Validation::success($positive)
                    ->guard(static fn() => Validation::fail($fail))
                    ->otherwise(static fn() => Validation::success($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );

            $assert->same(
                [$fail],
                Validation::success($positive)
                    ->guard(static fn() => Validation::fail($fail))
                    ->xotherwise(static fn() => Validation::success($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn($failures) => $failures->toList(),
                    ),
            );

            $assert->same(
                $negative,
                Validation::success($positive)
                    ->flatMap(static fn() => Validation::fail($fail))
                    ->xotherwise(static fn() => Validation::success($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn($failures) => $failures->toList(),
                    ),
            );
        });

    yield $prove
        ->proof('Validation::attempt()')
        ->given(
            Set::type(),
            Set::sequence(Set::type())->atLeast(1),
        )
        ->test(static function($assert, $success, $errors) {
            $assert->same(
                $success,
                Validation::success($success)
                    ->attempt(static fn() => new Exception)
                    ->unwrap(),
            );

            $rest = $errors;
            $first = \array_shift($rest);
            $failure = Validation::fail($first);

            foreach ($rest as $value) {
                $failure = $failure->and(
                    Validation::fail($value),
                    static fn() => null,
                );
            }

            $exception = new Exception;
            $assert->same(
                $exception,
                $failure
                    ->attempt(static function($failures) use ($assert, $errors, $exception) {
                        $assert->same($errors, $failures->toList());

                        return $exception;
                    })
                    ->match(
                        static fn() => null,
                        static fn($e) => $e,
                    ),
            );
        });
};
