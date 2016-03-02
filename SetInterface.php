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
     * @return string
     */
    public function type(): string;

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
     * @param \Closure $predicate
     *
     * @return self
     */
    public function filter(\Closure $predicate): self;

    /**
     * Apply the given function to all elements of the set
     *
     * @param \Closure $function
     *
     * @return self
     */
    public function foreach(\Closure $function): self;

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @param \Closure $discriminator
     *
     * @return MapInterface
     */
    public function groupBy(\Closure $discriminator): MapInterface;

    /**
     * Return a new set by applying the given function to all elements
     *
     * @param \Closure $function
     *
     * @return self
     */
    public function map(\Closure $function): self;

    /**
     * Return a sequence of 2 sets partitioned according to the given predicate
     *
     * @param \Closure $predicate
     *
     * @return SequenceInterface
     */
    public function partition(\Closure $predicate): SequenceInterface;

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
     * @param \Closure $function
     *
     * @return SequenceInterface
     */
    public function sort(\Closure $function): SequenceInterface;
}
