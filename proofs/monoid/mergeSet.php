<?php
declare(strict_types = 1);

use Innmind\Immutable\Monoid\MergeSet;
use Innmind\BlackBox\Set;
use Fixtures\Innmind\Immutable\Set as FSet;
use Properties\Innmind\Immutable\Monoid;

return static function() {
    $equals = static fn($a, $b) => $a->equals($b);
    $set = FSet::of(
        Set::integers()->between(0, 200),
        Set::integers()->between(1, 10),
    );

    yield properties(
        'MergeSet properties',
        Monoid::properties($set, $equals),
        Set::of(MergeSet::of()),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield proof(
            'MergeSet property',
            given($property),
            static fn($assert, $property) => $property->ensureHeldBy($assert, MergeSet::of()),
        );
    }
};
