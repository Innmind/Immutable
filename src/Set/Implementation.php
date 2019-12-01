<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Str,
};

/**
 * @template T
 */
interface Implementation extends \Countable
{
    public function isOfType(string $type): bool;

    /**
     * Return the type of this set
     */
    public function type(): string;
    public function size(): int;

    /**
     * @return list<T>
     */
    public function toArray(): array;

    /**
     * Intersect this set with the given one
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function intersect(self $set): self;

    /**
     * Add a element to the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self;

    /**
     * Check if the set contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool;

    /**
     * Remove the element from the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function remove($element): self;

    /**
     * Return the diff between this set and the given one
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function diff(self $set): self;

    /**
     * Check if the given set is identical to this one
     *
     * @param self<T> $set
     */
    public function equals(self $set): bool;

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self;

    /**
     * Apply the given function to all elements of the set
     *
     * @param callable(T): void $function
     */
    public function foreach(callable $function): void;

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @param callable(T) $discriminator
     *
     * @return Map<mixed, Set<T>>
     */
    public function groupBy(callable $discriminator): Map;

    /**
     * Return a new set by applying the given function to all elements
     *
     * @param callable(T): T $function
     *
     * @return self<T>
     */
    public function map(callable $function): self;

    /**
     * Return a sequence of 2 sets partitioned according to the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Set<T>>
     */
    public function partition(callable $predicate): Map;

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str;

    /**
     * Return a sequence sorted with the given function
     *
     * @param callable(T, T): int $function
     *
     * @return Sequence<T>
     */
    public function sort(callable $function): Sequence;

    /**
     * Create a new set with elements of both sets
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function merge(self $set): self;

    /**
     * Reduce the set to a single value
     *
     * @param mixed $carry
     * @param callable(mixed, T) $reducer
     *
     * @return mixed
     */
    public function reduce($carry, callable $reducer);

    /**
     * Return a set of the same type but without any value
     *
     * @return self<T>
     */
    public function clear(): self;
    public function empty(): bool;
}
