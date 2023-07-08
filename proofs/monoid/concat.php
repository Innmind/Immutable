<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Monoid\Concat,
    Str,
};
use Innmind\BlackBox\Set;
use Properties\Innmind\Immutable\Monoid;

return static function() {
    $equals = static fn($a, $b) => $a->toString() === $b->toString();
    $set = Set\Unicode::strings()->map(Str::of(...));

    yield properties(
        'Concat properties',
        Monoid::properties($set, $equals),
        Set\Elements::of(new Concat),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield proof(
            'Concat property',
            given($property),
            static fn($assert, $property) => $property->ensureHeldBy($assert, new Concat),
        );
    }
};
