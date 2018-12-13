<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Set of unique elements in a non deterministic order
 */
interface SetInterface extends SizeableInterface, PrimitiveInterface, \Countable, \Iterator
{
    /**
     * @param string $type Type T
     */
    public function __construct(string $type);

    /**
     * Return the type of this set
     *
     * @return Str
     */
    public function type(): Str;

    /**
     * Intersect this set with the given one
     *
     * @param self<T> $set
     *
     * @throws InvalidArgumentException If the sets are not of the same type
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
     *
     * @return bool
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
     *
     * @return bool
     */
    public function equals(self $set): bool;

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self;

    /**
     * Apply the given function to all elements of the set
     *
     * @param callable $function
     *
     * @return self<T>
     */
    public function foreach(callable $function): self;

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @param callable $discriminator
     *
     * @return MapInterface<mixed, self<T>>
     */
    public function groupBy(callable $discriminator): MapInterface;

    /**
     * Return a new set by applying the given function to all elements
     *
     * @param callable $function
     *
     * @return self<T>
     */
    public function map(callable $function): self;

    /**
     * Return a sequence of 2 sets partitioned according to the given predicate
     *
     * @param callable $predicate
     *
     * @return MapInterface<bool, self<T>>
     */
    public function partition(callable $predicate): MapInterface;

    /**
     * Concatenate all elements with the given separator
     *
     * @param string $separator
     *
     * @return Str
     */
    public function join(string $separator): Str;

    /**
     * Return a sequence sorted with the given function
     *
     * @param callable $function
     *
     * @return StreamInterface<T>
     */
    public function sort(callable $function): StreamInterface;

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
     * @param callable $reducer
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
