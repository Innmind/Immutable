<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid,
    Sequence,
};

/**
 * @template T
 * @psalm-immutable
 * @implements Monoid<Sequence<T>>
 */
final class Append implements Monoid
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
        /** @var Sequence<T> */
        return Sequence::of();
    }

    /**
     * @param Sequence<T> $a
     * @param Sequence<T> $b
     *
     * @return Sequence<T>
     */
    #[\Override]
    public function combine(mixed $a, mixed $b): mixed
    {
        return $a->append($b);
    }
}
