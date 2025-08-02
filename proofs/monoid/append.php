<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Monoid\Append,
    Sequence,
};
use Innmind\BlackBox\Set;
use Properties\Innmind\Immutable\Monoid;

return static function() {
    $equals = static fn($a, $b) => $a->equals($b);
    $set = Set::sequence(Set::type())->map(
        static fn($values) => Sequence::of(...$values),
    );

    yield properties(
        'Append properties',
        Monoid::properties($set, $equals),
        Set::of(Append::of()),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield proof(
            'Append property',
            given($property),
            static fn($assert, $property) => $property->ensureHeldBy($assert, Append::of()),
        );
    }
};
