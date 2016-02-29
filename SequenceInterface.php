<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface SequenceInterface extends SizeableInterface, PrimitiveInterface, \Countable, \Iterator, \ArrayAccess
{
    /**
     * Return the element at the given index
     *
     * @param int $index
     *
     * @throws OutOfBoundException
     *
     * @return mixed
     */
    public function get(int $index);

    /**
     * Return the diff between this sequence and another
     *
     * @param self $seq
     *
     * @return self
     */
    public function diff(self $seq): self;

    /**
     * Remove all duplicates from the sequence
     *
     * @return self
     */
    public function distinct(): self;

    /**
     * Remove the n first elements
     *
     * @param int $size
     *
     * @return self
     */
    public function drop(int $size): self;

    /**
     * Remove the n last elements
     *
     * @param int $size
     *
     * @return self
     */
    public function dropEnd(int $size): self;

    /**
     * Check if the two sequences are equal
     *
     * @param self $seq
     *
     * @return bool
     */
    public function equals(self $seq): bool;

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param \Closure $predicate
     *
     * @return self
     */
    public function filter(\Closure $predicate): self;

    /**
     * Apply the given function to all elements of the sequence
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
     * Return the first element
     *
     * @return mixed
     */
    public function first();

    /**
     * Return the last element
     *
     * @return mixed
     */
    public function last();

    /**
     * Check if the sequence contains the given element
     *
     * @param mixed $element
     *
     * @return bool
     */
    public function contains($element): bool;

    /**
     * Return the index for the given element
     *
     * @param mixed $element
     *
     * @throws ElementNotFoundException
     *
     * @return int
     */
    public function indexOf($element): int;

    /**
     * Return the list of indices
     *
     * @return self
     */
    public function indices(): self;

    /**
     * Return a new sequence by applying the given function to all elements
     *
     * @param \Closure $function
     *
     * @return self
     */
    public function map(\Closure $function): self;

    /**
     * Pad the sequence to a defined size with the given element
     *
     * @param int $size
     * @param mixed $element
     *
     * @return self
     */
    public function pad(int $size, $element): self;

    /**
     * Return a sequence of 2 sequences partitioned according to the given predicate
     *
     * @param \Closure $predicate
     *
     * @return self[self]
     */
    public function partition(\Closure $predicate): self;

    /**
     * Slice the sequence
     *
     * @param int $from
     * @param int $until
     *
     * @return self
     */
    public function slice(int $from, int $until): self;

    /**
     * Split the sequence in a sequence of 2 sequences splitted at the given position
     *
     * @param int $position
     *
     * @throws OutOfBoundException
     *
     * @return self[self]
     */
    public function splitAt(int $position): self;

    /**
     * Return a sequence with the n first elements
     *
     * @param int $size
     *
     * @return self
     */
    public function take(int $size): self;

    /**
     * Return a sequence with the n last elements
     *
     * @param int $size
     *
     * @return self
     */
    public function takeEnd(int $size): self;

    /**
     * Append the given sequence to the current one
     *
     * @param self $seq
     *
     * @return self
     */
    public function append(self $seq): self;

    /**
     * Return a sequence with all elements from the current one that exist
     * in the given one
     *
     * @param self $seq
     *
     * @return self
     */
    public function intersect(self $seq): self;

    /**
     * Concatenate all elements with the given separator
     *
     * @param string $separator
     *
     * @return StringPrimitive
     */
    public function join(string $separator): StringPrimitive;

    /**
     * Add the given element at the end of the sequence
     *
     * @param mixed $element
     *
     * @return self
     */
    public function add($element): self;
}
