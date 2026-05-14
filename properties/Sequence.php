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
        return Set::properties(
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
            Sequence\LookupFirstMaybe::class,
            Sequence\LookupFirstAttempt::class,
            Sequence\LookupFirstEither::class,
            Sequence\LookupLastMaybe::class,
            Sequence\LookupLastAttempt::class,
            Sequence\LookupLastEither::class,
            Sequence\Union::class,
        ];
    }
}
