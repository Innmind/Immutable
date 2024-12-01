<?php
declare(strict_types = 1);

use Innmind\Immutable\Either;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Either::memoize() any composition',
        given(Set\Type::any()->filter(static fn($value) => !\is_null($value))),
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
};
