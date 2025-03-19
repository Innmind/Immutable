<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * This class is here to mark methods that will perform side effects even though
 * the structure is immutable
 *
 * @psalm-immutable
 */
final class SideEffect
{
    private static ?self $instance = null;

    /**
     * @deprecated Use self::identity() instead
     */
    public function __construct()
    {
    }

    /**
     * @psalm-pure
     */
    public static function identity(): self
    {
        /** @psalm-suppress ImpureStaticProperty This will become an enum in the future */
        return self::$instance ??= new self;
    }
}
