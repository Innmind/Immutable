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
     * @param DataSet<I>|DataSet\Provider<I> $set
     * @param DataSet<int>|DataSet\Provider<int> $sizes
     *
     * @return DataSet<Structure<I>>
     */
    public static function of(
        DataSet|DataSet\Provider $set,
        DataSet|DataSet\Provider|null $sizes = null,
    ): DataSet {
        $sizes ??= DataSet::integers()->between(0, 100);

        return DataSet::compose(
            static fn($min, $max) => DataSet::sequence($set)
                ->between(
                    \min($min, $max),
                    \max($min, $max),
                )
                ->filter(static function(array $values): bool {
                    // checks unicity of values
                    return ISequence::mixed(...$values)->size() === Structure::mixed(...$values)->size();
                })
                ->map(static fn(array $values): Structure => Structure::of(...$values)),
            $sizes,
            $sizes,
        )->flatMap(static fn($sequences) => $sequences->unwrap());
    }
}
