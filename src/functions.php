<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    EmptySet,
    NoElementMatchingPredicateFound,
};

/**
 * @template T
 *
 * @param Set<T>|Sequence<T> $structure
 *
 * @return list<T>
 */
function unwrap($structure): array
{
    /** @psalm-suppress DocblockTypeContradiction */
    if (!$structure instanceof Set && !$structure instanceof Sequence) {
        $given = Type::determine($structure);

        throw new \TypeError("Argument 1 must be of type Set|Sequence, $given given");
    }

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
    /** @psalm-suppress DocblockTypeContradiction */
    if (!$structure instanceof Set && !$structure instanceof Sequence) {
        $given = Type::determine($structure);

        throw new \TypeError("Argument 2 must be of type Set|Sequence, $given given");
    }

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
    try {
        return $set->find(static fn(): bool => true);
    } catch (NoElementMatchingPredicateFound $e) {
        throw new EmptySet;
    }
}
