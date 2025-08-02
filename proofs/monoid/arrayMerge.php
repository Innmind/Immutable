<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Monoid\ArrayMerge,
    Sequence,
};
use Innmind\BlackBox\Set;
use Properties\Innmind\Immutable\Monoid;

return static function() {
    $equals = static fn($a, $b) => $a === $b;
    $set = Set::sequence(
        Set::compose(
            static fn($key, $value): array => [$key, $value],
            Set::integers()->between(0, 200),
            Set::integers()->between(0, 200),
        )->randomize(), // forced to randomize as the composite will try to reuse the same key
    )
        ->between(1, 10)
        ->filter(static function(array $pairs): bool {
            $keys = \array_column($pairs, 0);

            // checks unicity of values
            return Sequence::of(...$keys)->size() === Sequence::of(...$keys)->distinct()->size();
        })
        ->map(static fn($pairs) => \array_combine(
            \array_column($pairs, 0),
            \array_column($pairs, 1),
        ));

    yield properties(
        'ArrayMerge properties',
        Monoid::properties($set, $equals),
        Set::of(new ArrayMerge),
    );

    foreach (Monoid::list($set, $equals) as $property) {
        yield proof(
            'ArrayMerge property',
            given($property),
            static fn($assert, $property) => $property->ensureHeldBy($assert, new ArrayMerge),
        );
    }
};
