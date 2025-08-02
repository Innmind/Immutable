<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\Set;
use Innmind\Immutable\Sequence as Structure;

final class Sequence
{
    /**
     * @template I
     *
     * @param Set<I>|Set\Provider<I> $set
     * @param Set<int>|Set\Provider<int> $sizes
     *
     * @return Set<Structure<I>>
     */
    public static function of(
        Set|Set\Provider $set,
        Set|Set\Provider|null $sizes = null,
    ): Set {
        $sizes ??= Set::integers()->between(0, 100);

        return Set::compose(
            static fn($min, $max) => Set::sequence($set)
                ->between(
                    \min($min, $max),
                    \max($min, $max),
                )
                ->map(static fn(array $values): Structure => Structure::of(...$values)),
            $sizes,
            $sizes,
        )->flatMap(static fn($sequences) => $sequences->unwrap());
    }
}
