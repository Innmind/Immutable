<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Maybe,
    SideEffect,
};

/**
 * @template T
 * @psalm-immutable
 */
interface Implementation extends \Countable
{
    /**
     * Add a element to the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self;

    /**
     * @return 0|positive-int
     */
    public function size(): int;

    /**
     * Intersect this set with the given one
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function intersect(self $set): self;

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
    public function foreach(callable $function): SideEffect;

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     *
     * @param callable(T): D $discriminator
     *
     * @return Map<D, Set<T>>
     */
    public function groupBy(callable $discriminator): Map;

    /**
     * Return a new set by applying the given function to all elements
     *
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return self<S>
     */
    public function map(callable $function): self;

    /**
     * @template S
     *
     * @param callable(T): Set<S> $map
     * @param callable(Set<S>): Sequence\Implementation<S> $exfiltrate
     *
     * @return self<S>
     */
    public function flatMap(callable $map, callable $exfiltrate): self;

    /**
     * Return a sequence of 2 sets partitioned according to the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Set<T>>
     */
    public function partition(callable $predicate): Map;

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
     * @template I
     * @template R
     *
     * @param I $carry
     * @param callable(I|R, T): R $reducer
     *
     * @return I|R
     */
    public function reduce($carry, callable $reducer);

    /**
     * Return a set of the same type but without any value
     *
     * @return self<T>
     */
    public function clear(): self;
    public function empty(): bool;

    /**
     * @param callable(T): bool $predicate
     *
     * @return Maybe<T>
     */
    public function find(callable $predicate): Maybe;

    /**
     * Make sure every value conforms to the assertion
     *
     * @template R
     * @param R $carry
     * @param callable(R, T): R $assert
     *
     * @return self<T>
     */
    public function safeguard($carry, callable $assert): self;

    /**
     * @return Sequence\Implementation<T>
     */
    public function sequence(): Sequence\Implementation;

    /**
     * @return self<T>
     */
    public function memoize(): self;
}
