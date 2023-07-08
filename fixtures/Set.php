<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set as DataSet,
    Random,
};
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
        // this is not optimal but it allows to avoid a BC break
        $sizes ??= DataSet\Integers::between(0, 100);
        $range = [
            $sizes->values(Random::default)->current()->unwrap(),
            $sizes->values(Random::default)->current()->unwrap(),
        ];
        $min = \min($range);
        $max = \max($range);

        return DataSet\Sequence::of($set)
            ->between($min, $max)
            ->filter(static function(array $values): bool {
                // checks unicity of values
                return ISequence::mixed(...$values)->size() === Structure::mixed(...$values)->size();
            })
            ->map(static fn(array $values): Structure => Structure::of(...$values));
    }
}
