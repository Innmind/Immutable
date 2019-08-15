<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Collection of elements in a determined order (maybe with duplicates)
 */
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
     * Check the index exist
     *
     * @param int $index
     *
     * @return bool
     */
    public function has(int $index): bool;

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
     * @param callable $predicate
     *
     * @return self
     */
    public function filter(callable $predicate): self;

    /**
     * Apply the given function to all elements of the sequence
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
     * @return MapInterface<mixed, self>
     */
    public function groupBy(callable $discriminator): MapInterface;

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
     * @return StreamInterface<int>
     */
    public function indices(): StreamInterface;

    /**
     * Return a new sequence by applying the given function to all elements
     *
     * @param callable $function
     *
     * @return self
     */
    public function map(callable $function): self;

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
     * @param callable $predicate
     *
     * @return MapInterface<bool, self>
     */
    public function partition(callable $predicate): MapInterface;

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
     * @return StreamInterface<self>
     */
    public function splitAt(int $position): StreamInterface;

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
     * @return Str
     */
    public function join(string $separator): Str;

    /**
     * Add the given element at the end of the sequence
     *
     * @param mixed $element
     *
     * @return self
     */
    public function add($element): self;

    /**
     * Sort the sequence in a different order
     *
     * @param callable $function
     *
     * @return self
     */
    public function sort(callable $function): self;

    /**
     * Reduce the sequence to a single value
     *
     * @param mixed $carry
     * @param callable $reducer
     *
     * @return mixed
     */
    public function reduce($carry, callable $reducer);

    /**
     * Return the same sequence but in reverse order
     *
     * @return self<T>
     */
    public function reverse(): self;

    public function empty(): bool;
}
