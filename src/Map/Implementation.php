<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
    Set,
    Sequence,
    Pair,
    Maybe,
    SideEffect,
};

/**
 * @template T
 * @template S
 * @internal Dot not code against this interface
 * @psalm-immutable
 */
interface Implementation extends \Countable
{
    /**
     * Set a new key/value pair
     *
     * @param T $key
     * @param S $value
     *
     * @return self<T, S>
     */
    public function __invoke($key, $value): self;

    /**
     * @return int<0, max>
     */
    public function size(): int;

    /**
     * Return the element with the given key
     *
     * @param T $key
     *
     * @return Maybe<S>
     */
    public function get($key): Maybe;

    /**
     * Check if there is an element for the given key
     *
     * @param T $key
     */
    public function contains($key): bool;

    /**
     * Return an empty map given the same given type
     *
     * @return self<T, S>
     */
    public function clear(): self;

    /**
     * Check if the two maps are equal
     *
     * @param self<T, S> $map
     */
    public function equals(self $map): bool;

    /**
     * Filter the map based on the given predicate
     *
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    public function filter(callable $predicate): self;

    /**
     * Run the given function for each element of the map
     *
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): SideEffect;

    /**
     * Return a new map of pairs' sequences grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     *
     * @param callable(T, S): D $discriminator
     *
     * @return Map<D, Map<T, S>>
     */
    public function groupBy(callable $discriminator): Map;

    /**
     * Return all keys
     *
     * @return Set<T>
     */
    public function keys(): Set;

    /**
     * Return all values
     *
     * @return Sequence<S>
     */
    public function values(): Sequence;

    /**
     * Apply the given function on all elements and return a new map
     *
     * @template B
     *
     * @param callable(T, S): B $function
     *
     * @return self<T, B>
     */
    public function map(callable $function): self;

    /**
     * Remove the element with the given key
     *
     * @param T $key
     *
     * @return self<T, S>
     */
    public function remove($key): self;

    /**
     * Create a new map by combining both maps
     *
     * @param self<T, S> $map
     *
     * @return self<T, S>
     */
    public function merge(self $map): self;

    /**
     * Return a map of 2 maps partitioned according to the given predicate
     *
     * @param callable(T, S): bool $predicate
     *
     * @return Map<bool, Map<T, S>>
     */
    public function partition(callable $predicate): Map;

    /**
     * Reduce the map to a single value
     *
     * @template I
     * @template R
     *
     * @param I $carry
     * @param callable(I|R, T, S): R $reducer
     *
     * @return I|R
     */
    public function reduce($carry, callable $reducer);

    public function empty(): bool;

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return Maybe<Pair<T, S>>
     */
    public function find(callable $predicate): Maybe;

    /**
     * @return Sequence<Pair<T, S>>
     */
    public function toSequence(): Sequence;
}
