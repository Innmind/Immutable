<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable;

use Innmind\BlackBox\{
    Set,
    Property,
    Properties,
};

/**
 * @internal
 */
final class Sequence
{
    /**
     * @return Set<Properties>|Set\Provider<Properties>
     */
    public static function properties(): Set|Set\Provider
    {
        return Set\Properties::any(
            ...\array_map(
                static fn($class) => $class::any(),
                self::list(),
            ),
        );
    }

    /**
     * @return non-empty-list<class-string<Property>>
     */
    public static function list(): array
    {
        return [
            Sequence\Windows::class,
        ];
    }
}
