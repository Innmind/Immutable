<?php
declare(strict_types = 1);

use Innmind\Immutable\Monoid\MergeMap;
use Innmind\BlackBox\Set;
use Fixtures\Innmind\Immutable\Map;
use Properties\Innmind\Immutable\Monoid;

return static function() {
    $equals = static fn($a, $b) => $a->equals($b);
    $set = Map::of(
        Set\Integers::between(0, 200),
        Set\Integers::between(0, 200),
        Set\Integers::between(1, 10),
    );

    yield properties(
        'MergeMap properties',
        Monoid::properties($set, $equals),
        Set\Elements::of(MergeMap::of()),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield proof(
            'MergeMap property',
            given($property),
            static fn($assert, $property) => $property->ensureHeldBy($assert, MergeMap::of()),
        );
    }
};
