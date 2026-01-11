<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid,
    Set,
};

/**
 * @template T
 * @psalm-immutable
 * @implements Monoid<Set<T>>
 */
enum MergeSet implements Monoid
{
    case monoid;

    /**
     * @template C of object
     *
     * @param class-string<C> $class
     *
     * @return self<C>
     */
    #[\NoDiscard]
    public static function of(?string $class = null): self
    {
        /** @var self<C> */
        return self::monoid;
    }

    #[\Override]
    public function identity(): mixed
    {
        /** @var Set<T> */
        return Set::of();
    }

    /**
     * @param Set<T> $a
     * @param Set<T> $b
     *
     * @return Set<T>
     */
    #[\Override]
    public function combine(mixed $a, mixed $b): mixed
    {
        return $a->merge($b);
    }
}
