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
     * @param Set<I> $set
     *
     * @return Set<Structure<I>>
     */
    public static function of(Set $set, Set\Integers $sizes = null): Set
    {
        return Set\Decorate::immutable(
            static fn(array $values): Structure => Structure::of(...$values),
            Set\Sequence::of(
                $set,
                $sizes,
            ),
        );
    }
}
