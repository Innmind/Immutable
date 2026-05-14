<?php
declare(strict_types = 1);

use Innmind\Immutable\Monoid\MergeSet;
use Innmind\BlackBox\Set;
use Fixtures\Innmind\Immutable\Set as FSet;
use Properties\Innmind\Immutable\Monoid;

return static function($prove) {
    $equals = static fn($a, $b) => $a->equals($b);
    $set = FSet::of(
        Set::integers()->between(0, 200),
        Set::integers()->between(1, 10),
    );

    yield $prove->properties(
        'MergeSet properties',
        Monoid::properties($set, $equals),
        Set::of(static fn() => MergeSet::of()),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield $prove
            ->proof('MergeSet property')
            ->given($property)
            ->test(static fn($assert, $property) => $property->ensureHeldBy($assert, MergeSet::of()));
    }
};
