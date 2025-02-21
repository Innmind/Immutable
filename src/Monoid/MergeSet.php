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
final class MergeSet implements Monoid
{
    /**
     * @template C of object
     *
     * @param class-string<C> $class
     *
     * @return self<C>
     */
    public static function of(?string $class = null): self
    {
        /** @var self<C> */
        return new self;
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
