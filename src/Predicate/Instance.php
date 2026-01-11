<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Predicate;

use Innmind\Immutable\Predicate;

/**
 * @psalm-immutable
 */
final class Instance
{
    /**
     * @psalm-pure
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return Predicate<T>
     */
    #[\NoDiscard]
    public static function of(string $class): Predicate
    {
        /** @var Predicate<T> */
        return Predicate::of(
            static fn($value) => $value instanceof $class,
        );
    }
}
