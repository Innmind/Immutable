<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Set of unique elements in a non deterministic order
 */
interface SetInterface extends SizeableInterface, PrimitiveInterface, \Countable, \Iterator
{
    public function __construct(string $type);

    /**
     * Return the type of this set
     *
     * @return StringPrimitive
     */
    public function type(): StringPrimitive;

    /**
     * Intersect this set with the given one
     *
     * @param self $set
     *
     * @throws InvalidArgumentException If the sets are not of the same type
     *
     * @return self
     */
    public function intersect(self $set): self;

    /**
     * Add a element to the set
     *
     * @param mixed $element
     *
     * @return self
     */
    public function add($element): self;

    /**
     * Check if the set contains the given element
     *
     * @param mixed $element
     *
     * @return bool
     */
    public function contains($element): bool;

    /**
     * Remove the element from the set
     *
     * @param mixed $element
     *
     * @return self
     */
    public function remove($element): self;

    /**
     * Return the diff between this set and the given one
     *
     * @param self $set
     *
     * @return self
     */
    public function diff(self $set): self;

    /**
     * Check if the given set is identical to this one
     *
     * @param self $set
     *
     * @return bool
     */
    public function equals(self $set): bool;

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable $predicate
     *
     * @return self
     */
    public function filter(callable $predicate): self;

    /**
     * Apply the given function to all elements of the set
     *
     * @param callable $function
     *
     * @return self
     */
    public function foreach(callable $function): self;

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @param callable $discriminator
     *
     * @return MapInterface
     */
    public function groupBy(callable $discriminator): MapInterface;

    /**
     * Return a new set by applying the given function to all elements
     *
     * @param callable $function
     *
     * @return self
     */
    public function map(callable $function): self;

    /**
     * Return a sequence of 2 sets partitioned according to the given predicate
     *
     * @param callable $predicate
     *
     * @return MapInterface<bool, self>
     */
    public function partition(callable $predicate): MapInterface;

    /**
     * Concatenate all elements with the given separator
     *
     * @param string $separator
     *
     * @return StringPrimitive
     */
    public function join(string $separator): StringPrimitive;

    /**
     * Return a sequence sorted with the given function
     *
     * @param callable $function
     *
     * @return SequenceInterface
     */
    public function sort(callable $function): SequenceInterface;

    /**
     * Create a new set with elements of both sets
     *
     * @param self $set
     *
     * @return self
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
}
