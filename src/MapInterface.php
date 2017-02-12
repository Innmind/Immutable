<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface MapInterface extends SizeableInterface, \Countable, \Iterator, \ArrayAccess
{
    /**
     * @param string $keyType Type T
     * @param string $valueType Type S
     */
    public function __construct(string $keyType, string $valueType);

    /**
     * Return the key type for this map
     *
     * @return Str
     */
    public function keyType(): Str;

    /**
     * Return the value type for this map
     *
     * @return Str
     */
    public function valueType(): Str;

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
     * @throws ElementNotFoundException
     *
     * @return S
     */
    public function get($key);

    /**
     * Check if there is an element for the given key
     *
     * @param T $key
     *
     * @return bool
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
     *
     * @return bool
     */
    public function equals(self $map): bool;

    /**
     * Filter the map based on the given predicate
     *
     * @param callable $predicate
     *
     * @return self<T, S>
     */
    public function filter(callable $predicate): self;

    /**
     * Run the given function for each element of the map
     *
     * @param callable $function
     *
     * @return self<T, S>
     */
    public function foreach(callable $function): self;

    /**
     * Return a new map of pairs' sequences grouped by keys determined with the given
     * discriminator function
     *
     * @param callable $discriminator
     *
     * @return self<mixed, self<T, S>>
     */
    public function groupBy(callable $discriminator): self;

    /**
     * Return all keys
     *
     * @return SetInterface<T>
     */
    public function keys(): SetInterface;

    /**
     * Return all values
     *
     * @return StreamInterface<S>
     */
    public function values(): StreamInterface;

    /**
     * Apply the given function on all elements and return a new map
     *
     * Keys can't be modified
     *
     * @param callable $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self;

    /**
     * Concatenate all elements with the given separator
     *
     * @param string $separator
     *
     * @return Str
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
     * @param callable $predicate
     *
     * @return self<bool, self<T, S>>
     */
    public function partition(callable $predicate): self;

    /**
     * Reduce the map to a single value
     *
     * @param mixed $carry
     * @param callable $reducer
     *
     * @return mixed
     */
    public function reduce($carry, callable $reducer);
}
