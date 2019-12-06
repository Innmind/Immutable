<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
    Str,
    Set,
    Sequence,
    Pair,
    Exception\CannotGroupEmptyStructure,
    Exception\ElementNotFound,
};

/**
 * @template T
 * @template S
 * @internal Dot not code against this interface
 */
interface Implementation extends \Countable
{
    /**
     * Return the key type for this map
     */
    public function keyType(): string;

    /**
     * Return the value type for this map
     */
    public function valueType(): string;

    public function size(): int;

    /**
     * Set a new key/value pair
     *
     * @param T $key
     * @param S $value
     *
     * @return self<T, S>
     */
    public function put($key, $value): self;

    /**
     * Return the element with the given key
     *
     * @param T $key
     *
     * @throws ElementNotFound
     *
     * @return S
     */
    public function get($key);

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
    public function foreach(callable $function): void;

    /**
     * Return a new map of pairs' sequences grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     * @param callable(T, S): D $discriminator
     *
     * @throws CannotGroupEmptyStructure
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
     * Keys can't be modified
     *
     * @param callable(T, S): (S|Pair<T, S>) $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self;

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str;

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
     * @template R
     * @param R $carry
     * @param callable(R, T, S): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer);

    public function empty(): bool;

    /**
     * @template ST
     *
     * @param callable(T, S): \Generator<ST> $mapper
     *
     * @return Sequence<ST>
     */
    public function toSequenceOf(string $type, callable $mapper): Sequence;

    /**
     * @template ST
     *
     * @param callable(T, S): \Generator<ST> $mapper
     *
     * @return Set<ST>
     */
    public function toSetOf(string $type, callable $mapper): Set;

    /**
     * @template MT
     * @template MS
     *
     * @param callable(T, S): \Generator<MT, MS> $mapper
     *
     * @return Map<MT, MS>
     */
    public function toMapOf(string $key, string $value, callable $mapper): Map;
}
