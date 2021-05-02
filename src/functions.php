<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\EmptySet;

/**
 * Concatenate all elements with the given separator
 *
 * @param Set<string>|Sequence<string> $structure
 */
function join(string $separator, $structure): Str
{
    return Str::of(\implode($separator, $structure->toList()));
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
