<?php
declare(strict_types = 1);

use Innmind\Immutable\Maybe;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Maybe::defer() holds intermediary values',
        given(
            Set\Type::any(),
            Set\Type::any(),
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
        given(Set\Type::any()->filter(static fn($value) => !\is_null($value))),
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
};
