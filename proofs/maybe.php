<?php
declare(strict_types = 1);

use Innmind\Immutable\Maybe;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Maybe::defer() holds intermediary values',
        given(
            Set::type(),
            Set::type(),
        ),
        static function($assert, $value1, $value2) {
            $m1 = Maybe::defer(static function() use ($value1) {
                static $loaded = false;

                if ($loaded) {
                    throw new Exception;
                }

                $loaded = true;

                return Maybe::just($value1);
            });
            $m2 = $m1->map(static fn() => $value2);

            $assert->same(
                $value2,
                $m2->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->not()->throws(
                static fn() => $assert->same(
                    $value1,
                    $m1->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                ),
            );
        },
    );

    yield proof(
        'Maybe::memoize() any composition',
        given(Set::type()->filter(static fn($value) => !\is_null($value))),
        static function($assert, $value) {
            $loaded = false;
            $maybe = Maybe::defer(static fn() => Maybe::just($value))
                ->flatMap(static function() use ($value, &$loaded) {
                    return Maybe::defer(static function() use ($value, &$loaded) {
                        $loaded = true;

                        return Maybe::just($value);
                    });
                });

            $assert->false($loaded);
            $maybe->memoize();
            $assert->true($loaded);
        },
    );

    yield proof(
        'Maybe->attempt()',
        given(Set::type()),
        static function($assert, $value) {
            $assert->same(
                $value,
                Maybe::just($value)
                    ->attempt(static fn() => new Exception)
                    ->unwrap(),
            );

            $expected = new Exception;
            $assert->same(
                $expected,
                Maybe::nothing()
                    ->attempt(static fn() => $expected)
                    ->match(
                        static fn() => null,
                        static fn($error) => $error,
                    ),
            );
        },
    );

    yield proof(
        'Maybe::defer()->attempt()',
        given(Set::type()),
        static function($assert, $value) {
            $loaded = false;
            $attempt = Maybe::defer(static function() use (&$loaded, $value) {
                $loaded = true;

                return Maybe::just($value);
            })->attempt(static fn() => new Exception);

            $assert->false($loaded);
            $assert->same(
                $value,
                $attempt->unwrap(),
            );
            $assert->true($loaded);

            $expected = new Exception;
            $loaded = false;
            $attempt = Maybe::defer(static function() use (&$loaded) {
                $loaded = true;

                return Maybe::nothing();
            })->attempt(static fn() => $expected);

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
