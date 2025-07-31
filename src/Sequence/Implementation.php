<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Maybe,
    SideEffect,
    Identity,
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

    /**
     * @return int<0, max>
     */
    public function size(): int;

    /**
     * @return Iterator<T>
     */
    public function iterator(): Iterator;

    /**
     * Return the element at the given index
     *
     * @param int<0, max> $index
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
     * @param int<0, max> $size
     *
     * @return self<T>
     */
    public function drop(int $size): self;

    /**
     * Remove the n last elements
     *
     * @param int<0, max> $size
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
     * @return Maybe<int<0, max>>
     */
    public function indexOf($element): Maybe;

    /**
     * Return the list of indices
     *
     * @return self<int<0, max>>
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
     * @template C of Sequence<S>|Set<S>
     *
     * @param callable(T): C $map
     * @param callable(C): self<S> $exfiltrate
     *
     * @return self<S>
     */
    public function flatMap(callable $map, callable $exfiltrate): self;

    /**
     * @template S
     *
     * @param callable(self<T>): Sequence<S> $map
     *
     * @return Sequence<S>
     */
    public function via(callable $map): Sequence;

    /**
     * Pad the sequence to a defined size with the given element
     *
     * @param int<0, max> $size
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
     * @param int<0, max> $from
     * @param int<0, max> $until
     *
     * @return self<T>
     */
    public function slice(int $from, int $until): self;

    /**
     * Return a sequence with the n first elements
     *
     * @param int<0, max> $size
     *
     * @return self<T>
     */
    public function take(int $size): self;

    /**
     * Return a sequence with the n last elements
     *
     * @param int<0, max> $size
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
     * Prepend the given sequence to the current one
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function prepend(self $sequence): self;

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
     * Reduce the sequence to a single value but stops on the first failure
     *
     * @template I
     *
     * @param I $carry
     * @param callable(I, T, Sink\Continuation<I>): Sink\Continuation<I> $reducer
     *
     * @return I
     */
    public function sink($carry, callable $reducer): mixed;

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
     * @return Identity<self<T>>
     */
    public function toIdentity(): Identity;

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
     * @template C of Sequence<T>|Set<T>
     *
     * @param callable(self<T>): C $wrap
     * @param callable(T, C): R $match
     * @param callable(): R $empty
     *
     * @return R
     */
    public function match(callable $wrap, callable $match, callable $empty);

    /**
     * @template S
     *
     * @param self<S> $sequence
     *
     * @return self<array{T, S}>
     */
    public function zip(self $sequence): self;

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
     * @template A
     *
     * @param callable(T|A, T): Sequence<A> $map
     * @param callable(Sequence<A>): Implementation<A> $exfiltrate
     *
     * @return self<T|A>
     */
    public function aggregate(callable $map, callable $exfiltrate): self;

    /**
     * @return Primitive<T>
     */
    public function memoize(): Primitive;

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    public function dropWhile(callable $condition): self;

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    public function takeWhile(callable $condition): self;
}
