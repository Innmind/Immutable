<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable;

use Innmind\BlackBox\{
    Set,
    Property,
};

final class Monoid
{
    /**
     * @template T
     *
     * @param Set<T> $values
     * @param callable(T, T): bool $equals
     *
     * @return Set<Property>
     */
    public static function properties(Set $values, callable $equals): Set
    {
        return Set\Properties::any(...self::list($values, $equals))->atMost(10);
    }

    /**
     * @template T
     *
     * @param Set<T> $values
     * @param callable(T, T): bool $equals
     *
     * @return non-empty-list<Property>
     */
    public static function list(Set $values, callable $equals): array
    {
        return [
            Monoid\Identity::of($values, $equals),
            Monoid\Associativity::of($values, $equals),
        ];
    }
}
