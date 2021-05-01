<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\EmptySet;

/**
 * @template T
 *
 * @param Set<T>|Sequence<T> $structure
 *
 * @return list<T>
 */
function unwrap($structure): array
{
    /**
     * @psalm-suppress MixedAssignment
     *
     * @var list<T>
     */
    return $structure->reduce(
        [],
        static function(array $carry, $t): array {
            $carry[] = $t;

            return $carry;
        },
    );
}

/**
 * Concatenate all elements with the given separator
 *
 * @param Set<string>|Sequence<string> $structure
 */
function join(string $separator, $structure): Str
{
    return Str::of(\implode($separator, unwrap($structure)));
}

/**
 * @template T
 *
 *
 * @param Set<T> $set
 * @throws EmptySet
 *
 * @return T
 */
function first(Set $set)
{
    /**
     * @psalm-suppress MissingClosureReturnType
     * @var T
     */
    return $set->find(static fn(): bool => true)->match(
        static fn($value) => $value,
        static function() {
            throw new EmptySet;
        },
    );
}
