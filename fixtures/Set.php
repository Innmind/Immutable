<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\Set as DataSet;
use Innmind\Immutable\{
    Set as Structure,
    Sequence as ISequence,
};

final class Set
{
    /**
     * @template I
     *
     * @param Set<I> $set
     *
     * @return Set<Structure<I>>
     */
    public static function of(DataSet $set, DataSet\Integers $sizes = null): DataSet
    {
        return DataSet\Decorate::immutable(
            static fn(array $values): Structure => Structure::of(...$values),
            DataSet\Sequence::of(
                $set,
                $sizes,
            )->filter(static function(array $values): bool {
                // checks unicity of values
                return ISequence::mixed(...$values)->size() === Structure::mixed(...$values)->size();
            }),
        );
    }
}
