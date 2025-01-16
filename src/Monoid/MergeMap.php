<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid,
    Map,
};

/**
 * @template T
 * @template U
 * @psalm-immutable
 * @implements Monoid<Map<T, U>>
 */
final class MergeMap implements Monoid
{
    /**
     * @template A of object
     * @template B of object
     *
     * @param class-string<A> $key
     * @param class-string<B> $value
     *
     * @return self<A, B>
     */
    public static function of(?string $key = null, ?string $value = null): self
    {
        /** @var self<A, B> */
        return new self;
    }

    public function identity(): mixed
    {
        /** @var Map<T, U> */
        return Map::of();
    }

    /**
     * @param Map<T, U> $a
     * @param Map<T, U> $b
     *
     * @return Map<T, U>
     */
    public function combine(mixed $a, mixed $b): mixed
    {
        return $a->merge($b);
    }
}
