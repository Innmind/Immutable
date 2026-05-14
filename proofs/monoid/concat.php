<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Monoid\Concat,
    Str,
};
use Innmind\BlackBox\Set;
use Properties\Innmind\Immutable\Monoid;

return static function($prove) {
    $equals = static fn($a, $b) => $a->toString() === $b->toString();
    $set = Set::strings()->unicode()->map(Str::of(...));

    yield $prove->properties(
        'Concat properties',
        Monoid::properties($set, $equals),
        Set::of(static fn() => Concat::monoid),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield $prove
            ->proof('Concat property')
            ->given($property)
            ->test(static fn($assert, $property) => $property->ensureHeldBy($assert, Concat::monoid));
    }
};
