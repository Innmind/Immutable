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
     * @param DataSet<I> $set
     * @param DataSet<int> $sizes
     *
     * @return DataSet<Structure<I>>
     */
    public static function of(DataSet $set, ?DataSet $sizes = null): DataSet
    {
        if (\interface_exists(DataSet\Implementation::class)) {
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
