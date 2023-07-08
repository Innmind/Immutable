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
     *
     * @return Set<Structure<I>>
     */
    public static function of(Set $set, Set\Integers $sizes = null): Set
    {
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
