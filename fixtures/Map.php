<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set,
    Random,
};
use Innmind\Immutable\{
    Map as Structure,
    Set as ISet,
    Sequence as ISequence,
};

final class Map
{
    /**
     * @deprecated Will be removed in the next major release
     *
     * @template I
     * @template J
     *
     * @param Set<I> $keys
     * @param Set<J> $values
     *
     * @return Set<Structure<I, J>>
     */
    public static function of(
        Set $keys,
        Set $values,
        Set\Integers $sizes = null,
    ): Set {
        // this is not optimal but it allows to avoid a BC break
        $sizes ??= Set\Integers::between(0, 100);
        $range = [
            $sizes->values(Random::default)->current()->unwrap(),
            $sizes->values(Random::default)->current()->unwrap(),
        ];
        $min = \min($range);
        $max = \max($range);

        return Set\Decorate::immutable(
            static fn(array $pairs): Structure => \array_reduce(
                $pairs,
                static fn(Structure $map, array $pair): Structure => ($map)($pair[0], $pair[1]),
                Structure::of(),
            ),
            Set\Sequence::of(
                Set\Randomize::of( // forced to randomize as the composite will try to reuse the same key
                    Set\Composite::immutable(
                        static fn($key, $value): array => [$key, $value],
                        $keys,
                        $values,
                    ),
                ),
            )
                ->between($min, $max)
                ->filter(static function(array $pairs): bool {
                    $keys = \array_column($pairs, 0);

                    // checks unicity of values
                    return ISequence::mixed(...$keys)->size() === ISet::mixed(...$keys)->size();
                }),
        );
    }
}
