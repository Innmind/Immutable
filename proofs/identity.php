<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Identity,
    Sequence,
};
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
        static function($assert, $initial, $expected) {
            $assert->same(
                $expected,
                Identity::of($initial)
                    ->flatMap(static function($value) use ($assert, $initial, $expected) {
                        $assert->same($initial, $value);

                        return Identity::of($expected);
                    })
                    ->unwrap(),
            );

            $loaded = 0;
            $identity = Identity::defer(static function() use (&$loaded, $initial) {
                $loaded++;

                return $initial;
            })->flatMap(static function($value) use ($assert, $initial, $expected) {
                $assert->same($initial, $value);

                return Identity::of($expected);
            });
            $assert->same(0, $loaded);
            $assert->same(
                $expected,
                $identity->unwrap(),
            );
            $assert->same(1, $loaded);
            $assert->same(
                $expected,
                $identity->unwrap(),
            );
            $assert->same(1, $loaded);

            $loaded = 0;
            $identity = Identity::lazy(static function() use (&$loaded, $initial) {
                $loaded++;

                return $initial;
            })->flatMap(static function($value) use ($assert, $initial, $expected) {
                $assert->same($initial, $value);

                return Identity::of($expected);
            });
            $assert->same(0, $loaded);
            $assert->same(
                $expected,
                $identity->unwrap(),
            );
            $assert->same(1, $loaded);
            $assert->same(
                $expected,
                $identity->unwrap(),
            );
            $assert->same(2, $loaded);
        },
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

    yield proof(
        'Identity::toSequence()',
        given(Set\Sequence::of(Set\Type::any())),
        static function($assert, $values) {
            $inMemory = Sequence::of(...$values);

            $assert->same(
                $values,
                $inMemory
                    ->toIdentity()
                    ->toSequence()
                    ->flatMap(static fn($sequence) => $sequence)
                    ->toList(),
            );

            $loaded = 0;
            $deferred = Sequence::defer((static function() use (&$loaded, $values) {
                yield from $values;
                $loaded++;
            })());
            $sequence = $deferred
                ->toIdentity()
                ->toSequence()
                ->flatMap(static fn($sequence) => $sequence);

            $assert->same(0, $loaded);
            $assert->same(
                $values,
                $sequence->toList(),
            );
            $assert->same(1, $loaded);
            $assert->same(
                $values,
                $sequence->toList(),
            );
            $assert->same(1, $loaded);

            $loaded = 0;
            $lazy = Sequence::lazy(static function() use (&$loaded, $values) {
                yield from $values;
                $loaded++;
            });
            $sequence = $lazy
                ->toIdentity()
                ->toSequence()
                ->flatMap(static fn($sequence) => $sequence);

            $assert->same(0, $loaded);
            $assert->same(
                $values,
                $sequence->toList(),
            );
            $assert->same(1, $loaded);
            $assert->same(
                $values,
                $sequence->toList(),
            );
            $assert->same(2, $loaded);
        },
    );

    yield proof(
        'Identity::defer() holds intermediary values',
        given(
            Set\Type::any(),
            Set\Type::any(),
        ),
        static function($assert, $value1, $value2) {
            $m1 = Identity::defer(static function() use ($value1) {
                static $loaded = false;

                if ($loaded) {
                    throw new Exception;
                }

                $loaded = true;

                return $value1;
            });
            $m2 = $m1->map(static fn() => $value2);

            $assert->same(
                $value2,
                $m2->unwrap(),
            );
            $assert->not()->throws(
                static fn() => $assert->same(
                    $value1,
                    $m1->unwrap(),
                ),
            );
        },
    );
};
