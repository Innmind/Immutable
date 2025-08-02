<?php
declare(strict_types = 1);

use Innmind\Immutable\Either;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Either::memoize() any composition',
        given(Set::type()->filter(static fn($value) => !\is_null($value))),
        static function($assert, $value) {
            $loaded = false;
            $either = Either::defer(static fn() => Either::right($value))
                ->flatMap(static function() use ($value, &$loaded) {
                    return Either::defer(static function() use ($value, &$loaded) {
                        $loaded = true;

                        return Either::right($value);
                    });
                });

            $assert->false($loaded);
            $either->memoize();
            $assert->true($loaded);
        },
    );

    yield proof(
        'Either->attempt()',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $right, $left) {
            $assert->same(
                $right,
                Either::right($right)
                    ->attempt(static fn() => new Exception)
                    ->unwrap(),
            );

            $expected = new Exception;
            $assert->same(
                $expected,
                Either::left($left)
                    ->attempt(static function($value) use ($assert, $left, $expected) {
                        $assert->same($left, $value);

                        return $expected;
                    })
                    ->match(
                        static fn() => null,
                        static fn($error) => $error,
                    ),
            );
        },
    );

    yield proof(
        'Either::defer()->attempt()',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $right, $left) {
            $loaded = false;
            $attempt = Either::defer(static function() use (&$loaded, $right) {
                $loaded = true;

                return Either::right($right);
            })->attempt(static fn() => new Exception);

            $assert->false($loaded);
            $assert->same(
                $right,
                $attempt->unwrap(),
            );
            $assert->true($loaded);

            $expected = new Exception;
            $loaded = false;
            $attempt = Either::defer(static function() use (&$loaded, $left) {
                $loaded = true;

                return Either::left($left);
            })->attempt(static function($value) use ($assert, $left, $expected) {
                $assert->same($left, $value);

                return $expected;
            });

            $assert->false($loaded);
            $assert->same(
                $expected,
                $attempt->match(
                    static fn() => null,
                    static fn($error) => $error,
                ),
            );
            $assert->true($loaded);
        },
    );
};
