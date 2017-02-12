<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Sequence of elements of the same type
 */
interface StreamInterface extends SizeableInterface, PrimitiveInterface, \Countable, \Iterator, \ArrayAccess
{
    /**
     * Type of the elements
     *
     * @return Str
     */
    public function type(): Str;

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
     * Return the diff between this stream and another
     *
     * @param self<T> $stream
     *
     * @return self<T>
     */
    public function diff(self $stream): self;

    /**
     * Remove all duplicates from the stream
     *
     * @return self<T>
     */
    public function distinct(): self;

    /**
     * Remove the n first elements
     *
     * @param int $size
     *
     * @return self<T>
     */
    public function drop(int $size): self;

    /**
     * Remove the n last elements
     *
     * @param int $size
     *
     * @return self<T>
     */
    public function dropEnd(int $size): self;

    /**
     * Check if the two streams are equal
     *
     * @param self<T> $stream
     *
     * @return bool
     */
    public function equals(self $stream): bool;

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self;

    /**
     * Apply the given function to all elements of the stream
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
     * @throws GroupEmptySequenceException
     *
     * @return MapInterface<mixed, StreamInterface<T>>
     */
    public function groupBy(callable $discriminator): MapInterface;

    /**
     * Return the first element
     *
     * @return T
     */
    public function first();

    /**
     * Return the last element
     *
     * @return T
     */
    public function last();

    /**
     * Check if the stream contains the given element
     *
     * @param T $element
     *
     * @return bool
     */
    public function contains($element): bool;

    /**
     * Return the index for the given element
     *
     * @param T $element
     *
     * @throws ElementNotFoundException
     *
     * @return int
     */
    public function indexOf($element): int;

    /**
     * Return the list of indices
     *
     * @return self<int>
     */
    public function indices(): self;

    /**
     * Return a new stream by applying the given function to all elements
     *
     * @param callable $function
     *
     * @return self<T>
     */
    public function map(callable $function): self;

    /**
     * Pad the stream to a defined size with the given element
     *
     * @param int $size
     * @param T $element
     *
     * @return self<T>
     */
    public function pad(int $size, $element): self;

    /**
     * Return a stream of 2 streams partitioned according to the given predicate
     *
     * @param callable $predicate
     *
     * @return MapInterface<bool, self<T>>
     */
    public function partition(callable $predicate): MapInterface;

    /**
     * Slice the stream
     *
     * @param int $from
     * @param int $until
     *
     * @return self<T>
     */
    public function slice(int $from, int $until): self;

    /**
     * Split the stream in a stream of 2 streams splitted at the given position
     *
     * @param int $position
     *
     * @throws OutOfBoundException
     *
     * @return self<self<T>>
     */
    public function splitAt(int $position): self;

    /**
     * Return a stream with the n first elements
     *
     * @param int $size
     *
     * @return self<T>
     */
    public function take(int $size): self;

    /**
     * Return a stream with the n last elements
     *
     * @param int $size
     *
     * @return self<T>
     */
    public function takeEnd(int $size): self;

    /**
     * Append the given stream to the current one
     *
     * @param self $stream
     *
     * @return self<T>
     */
    public function append(self $stream): self;

    /**
     * Return a stream with all elements from the current one that exist
     * in the given one
     *
     * @param self<T> $stream
     *
     * @return self<T>
     */
    public function intersect(self $stream): self;

    /**
     * Concatenate all elements with the given separator
     *
     * @param string $separator
     *
     * @return Str
     */
    public function join(string $separator): Str;

    /**
     * Add the given element at the end of the stream
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self;

    /**
     * Sort the stream in a different order
     *
     * @param callable $function
     *
     * @return self<T>
     */
    public function sort(callable $function): self;

    /**
     * Reduce the stream to a single value
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
}
