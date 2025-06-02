<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Monoid;

use Innmind\Immutable\Monoid;

/**
 * @template T of array-key
 * @template U
 * @psalm-immutable
 * @implements Monoid<array<T, U>>
 */
final class ArrayMerge implements Monoid
{
    #[\Override]
    public function identity(): mixed
    {
        /** @var array<T, U> */
        return [];
    }

    /**
     * @param array<T, U> $a
     * @param array<T, U> $b
     *
     * @return array<T, U>
     */
    #[\Override]
    public function combine(mixed $a, mixed $b): mixed
    {
        return \array_replace($a, $b);
    }
}
