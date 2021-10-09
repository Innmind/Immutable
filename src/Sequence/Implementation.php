<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
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
     * Add the given element at the end of the sequence
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self;

    public function size(): int;

    /**
     * @return \Iterator<int, T>
     */
    public function iterator(): \Iterator;

    /**
     * Return the element at the given index
     *
     * @return Maybe<T>
     */
    public function get(int $index): Maybe;

    /**
     * Return the diff between this sequence and another
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function diff(self $sequence): self;

    /**
     * Remove all duplicates from the sequence
     *
     * @return self<T>
     */
    public function distinct(): self;

    /**
     * Remove the n first elements
     *
     * @return self<T>
     */
    public function drop(int $size): self;

    /**
     * Remove the n last elements
     *
     * @return self<T>
     */
    public function dropEnd(int $size): self;

    /**
     * Check if the two sequences are equal
     *
     * @param self<T> $sequence
     */
    public function equals(self $sequence): bool;

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self;

    /**
     * Apply the given function to all elements of the sequence
     *
     * @param callable(T): void $function
     */
    public function foreach(callable $function): SideEffect;

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     * @param callable(T): D $discriminator
     *
     * @return Map<D, Sequence<T>>
     */
    public function groupBy(callable $discriminator): Map;

    /**
     * Return the first element
     *
     * @return Maybe<T>
     */
    public function first(): Maybe;

    /**
     * Return the last element
     *
     * @return Maybe<T>
     */
    public function last(): Maybe;

    /**
     * Check if the sequence contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool;

    /**
     * Return the index for the given element
     *
     * @param T $element
     *
     * @return Maybe<int>
     */
    public function indexOf($element): Maybe;

    /**
     * Return the list of indices
     *
     * @return self<int>
     */
    public function indices(): self;

    /**
     * Return a new sequence by applying the given function to all elements
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
     * @param callable(T): Sequence<S> $map
     * @param callable(Sequence<S>): self<S> $exfiltrate
     *
     * @return Sequence<S>
     */
    public function flatMap(callable $map, callable $exfiltrate): Sequence;

    /**
     * Pad the sequence to a defined size with the given element
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function pad(int $size, $element): self;

    /**
     * Return a sequence of 2 sequences partitioned according to the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Sequence<T>>
     */
    public function partition(callable $predicate): Map;

    /**
     * Slice the sequence
     *
     * @return self<T>
     */
    public function slice(int $from, int $until): self;

    /**
     * Return a sequence with the n first elements
     *
     * @return self<T>
     */
    public function take(int $size): self;

    /**
     * Return a sequence with the n last elements
     *
     * @return self<T>
     */
    public function takeEnd(int $size): self;

    /**
     * Append the given sequence to the current one
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function append(self $sequence): self;

    /**
     * Return a sequence with all elements from the current one that exist
     * in the given one
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function intersect(self $sequence): self;

    /**
     * Sort the sequence in a different order
     *
     * @param callable(T, T): int $function
     *
     * @return self<T>
     */
    public function sort(callable $function): self;

    /**
     * Reduce the sequence to a single value
     *
     * @template R
     * @param R $carry
     * @param callable(R, T): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer);

    /**
     * Return a set of the same type but without any value
     *
     * @return self<T>
     */
    public function clear(): self;

    /**
     * Return the same sequence but in reverse order
     *
     * @return self<T>
     */
    public function reverse(): self;

    public function empty(): bool;

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence;

    /**
     * @return Set<T>
     */
    public function toSet(): Set;

    /**
     * @param callable(T): bool $predicate
     *
     * @return Maybe<T>
     */
    public function find(callable $predicate): Maybe;

    /**
     * @template R
     *
     * @param callable(self<T>): Sequence<T> $wrap
     * @param callable(T, Sequence<T>): R $match
     * @param callable(): R $empty
     *
     * @return R
     */
    public function match(callable $wrap, callable $match, callable $empty);
}
