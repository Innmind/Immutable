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
     * @throws OutOfBoundException
     *
     * @return mixed
     */
    public function get(int $index);

    /**
     * Check the index exist
     */
    public function has(int $index): bool;

    /**
     * Return the diff between this sequence and another
     */
    public function diff(self $seq): self;

    /**
     * Remove all duplicates from the sequence
     */
    public function distinct(): self;

    /**
     * Remove the n first elements
     */
    public function drop(int $size): self;

    /**
     * Remove the n last elements
     */
    public function dropEnd(int $size): self;

    /**
     * Check if the two sequences are equal
     */
    public function equals(self $seq): bool;

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable(mixed): bool $predicate
     */
    public function filter(callable $predicate): self;

    /**
     * Apply the given function to all elements of the sequence
     *
     * @param callable(mixed): void $function
     */
    public function foreach(callable $function): self;

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @param callable(mixed) $discriminator
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
     */
    public function contains($element): bool;

    /**
     * Return the index for the given element
     *
     * @param mixed $element
     *
     * @throws ElementNotFoundException
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
     * @param callable(mixed) $function
     */
    public function map(callable $function): self;

    /**
     * Pad the sequence to a defined size with the given element
     *
     * @param mixed $element
     */
    public function pad(int $size, $element): self;

    /**
     * Return a sequence of 2 sequences partitioned according to the given predicate
     *
     * @param callable(mixed): bool $predicate
     *
     * @return MapInterface<bool, self>
     */
    public function partition(callable $predicate): MapInterface;

    public function slice(int $from, int $until): self;

    /**
     * Split the sequence in a sequence of 2 sequences splitted at the given position
     *
     * @throws OutOfBoundException
     *
     * @return StreamInterface<self>
     */
    public function splitAt(int $position): StreamInterface;

    /**
     * Return a sequence with the n first elements
     */
    public function take(int $size): self;

    /**
     * Return a sequence with the n last elements
     */
    public function takeEnd(int $size): self;

    /**
     * Append the given sequence to the current one
     */
    public function append(self $seq): self;

    /**
     * Return a sequence with all elements from the current one that exist
     * in the given one
     */
    public function intersect(self $seq): self;

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str;

    /**
     * Add the given element at the end of the sequence
     *
     * @param mixed $element
     */
    public function add($element): self;

    /**
     * Sort the sequence in a different order
     *
     * @param callable(mixed): int $function
     */
    public function sort(callable $function): self;

    /**
     * Reduce the sequence to a single value
     *
     * @param mixed $carry
     * @param callable(mixed, mixed) $reducer
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
