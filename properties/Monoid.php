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
        return Set\Properties::chooseFrom(
            new Set\Either(...self::list($values, $equals)),
            Set\Integers::between(1, 10),
        );
    }

    /**
     * @template T
     *
     * @param Set<T> $values
     * @param callable(T, T): bool $equals
     *
     * @return list<Property>
     */
    public static function list(Set $values, callable $equals): array
    {
        return [
            Monoid\Identity::any($values, $equals),
            Monoid\Associativity::any($values, $equals),
        ];
    }
}
