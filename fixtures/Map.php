<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\Set;
use Innmind\Immutable\Map as Structure;

final class Map
{
    /**
     * @template I
     * @template J
     *
     * @param Set<I> $keys
     * @param Set<J> $values
     *
     * @return Set<Structure<I, J>>
     */
    public static function of(
        string $keyType,
        string $valueType,
        Set $keys,
        Set $values,
        Set\Integers $sizes = null
    ): Set {
        return Set\Decorate::immutable(
            static fn(array $pairs): Structure => \array_reduce(
                $pairs,
                static fn(Structure $map, array $pair): Structure => ($map)($pair[0], $pair[1]),
                Structure::of($keyType, $valueType),
            ),
            Set\Sequence::of(
                new Set\Randomize( // forced to randomize as the composite will try to reuse the same key
                    Set\Composite::immutable(
                        static fn($key, $value): array => [$key, $value],
                        $keys,
                        $values,
                    ),
                ),
                $sizes,
            ),
        );
    }
}
