<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\Set as DataSet;
use Innmind\Immutable\Set as Structure;

final class Set
{
    /**
     * @template I
     *
     * @param Set<I> $set
     *
     * @return Set<Structure<I>>
     */
    public static function of(string $type, DataSet $set, DataSet\Integers $sizes = null): DataSet
    {
        return DataSet\Decorate::immutable(
            static fn(array $values): Structure => Structure::of($type, ...$values),
            DataSet\Sequence::of(
                $set,
                $sizes,
            ),
        );
    }
}
