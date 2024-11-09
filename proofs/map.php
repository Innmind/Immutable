<?php
declare(strict_types = 1);

use Innmind\Immutable\Map;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Map::toSequence()',
        given(
            Set\Sequence::of(Set\Type::any()),
            Set\Sequence::of(Set\Type::any()),
        ),
        static function($assert, $keys, $values) {
            $map = Map::of();

            foreach ($keys as $index => $key) {
                $map = ($map)($key, $values[$index] ?? null);
            }

            $assert->true(
                $map->equals(Map::of(
                    ...$map
                        ->toSequence()
                        ->map(static fn($pair) => [$pair->key(), $pair->value()])
                        ->toList(),
                )),
            );
        },
    );
};
