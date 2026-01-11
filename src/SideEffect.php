<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * This class is here to mark methods that will perform side effects even though
 * the structure is immutable
 *
 * @psalm-immutable
 */
enum SideEffect
{
    case identity;

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function identity(): self
    {
        return self::identity;
    }
}
