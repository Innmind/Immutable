<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set,
    Random,
};
use Innmind\Immutable\Sequence as Structure;

final class Sequence
{
    /**
     * @template I
     *
     * @param Set<I> $set
     * @param Set<int> $sizes
     *
     * @return Set<Structure<I>>
     */
    public static function of(Set $set, ?Set $sizes = null): Set
    {
        if (\interface_exists(Set\Implementation::class)) {
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

        // this is not optimal but it allows to avoid a BC break
        $sizes ??= Set\Integers::between(0, 100);
        $range = [
            $sizes->values(Random::default)->current()->unwrap(),
            $sizes->values(Random::default)->current()->unwrap(),
        ];
        $min = \min($range);
        $max = \max($range);

        return Set\Sequence::of($set)
            ->between($min, $max)
            ->map(static fn(array $values): Structure => Structure::of(...$values));
    }
}
