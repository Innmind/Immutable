<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface CollectionInterface extends PrimitiveInterface, \Iterator, \ArrayAccess, \Countable
{
    const SORT_REGULAR = 0;
    const SORT_NUMERIC = 1;
    const SORT_STRING = 2;
    const SORT_LOCALE_STRING = 5;
    const SORT_NATURAL = 6;
    const SORT_FLAG_CASE = 8;

    /**
     * Returns a new collection containing the elements matching the filter
     *
     * @param callable $filter
     *
     * @return self
     */
    public function filter(callable $filter = null): self;

    /**
     * Returns a new collection which contains the intersection of both collections
     *
     * @param self $collection
     *
     * @return self
     */
    public function intersect(self $collection): self;

    /**
     * Split the collection into a collection of collections of the given size
     *
     * @param int $size
     *
     * @return self
     */
    public function chunk(int $size): self;

    /**
     * Returns a new collection without the first element of the current one
     *
     * @return self
     */
    public function shift(): self;

    /**
     * Reduce the given collection to a single value through the given reducer
     *
     * @param callable $reducer
     * @param mixed $initial
     *
     * @return mixed
     */
    public function reduce(callable $reducer, $initial = null);

    /**
     * Returns the key of the matching element
     *
     * @param mixed $needle
     * @param boolean $strict
     *
     * @throws ValueNotFoundException If the search is not successful
     *
     * @return mixed
     */
    public function search($needle, bool $strict = true);

    /**
     * Returns a new collection of the collections' intersection given the intersecter
     *
     * @param self $collection
     * @param callable $intersecter
     *
     * @return self
     */
    public function uintersect(self $collection, callable $intersecter): self;

    /**
     * Returns a new collection of the collections' intersection via keys
     *
     * @param self $collection
     *
     * @return self
     */
    public function keyIntersect(self $collection): self;

    /**
     * Return a new collection with each value transformed with the given mapper
     *
     * @param callable $mapper
     *
     * @return self
     */
    public function map(callable $mapper): self;

    /**
     * Returns a new collection with each value padded to the given size
     *
     * @param int $size
     * @param mixed $value
     *
     * @return self
     */
    public function pad(int $size, $value): self;

    /**
     * Returns a new collection without the last element of the current one
     *
     * @return self
     */
    public function pop(): self;

    /**
     * Sum all the values of the collection
     *
     * @return number
     */
    public function sum();

    /**
     * Returns a new collection containing the difference of each collections
     *
     * @param self $collections
     *
     * @return self
     */
    public function diff(self $collections): self;

    /**
     * Return a new collection with the keys and values flipped
     *
     * @return self
     */
    public function flip(): self;

    /**
     * Return a new collection containing the current one keys
     *
     * @param mixed $search
     * @param boolean $strict
     *
     * @return self
     */
    public function keys($search = null, bool $strict = true): self;

    /**
     * Returns a new collection with the given value added at the end
     *
     * @param mixed $value
     *
     * @return self
     */
    public function push($value): self;

    /**
     * Return a new collection containing one or more keys of the randomly picked values
     *
     * @param int $num
     *
     * @throws OutOfBoundException If the num is higher than the collection length
     *
     * @return self
     */
    public function rand(int $num = 1): self;

    /**
     * Returns a new collection with both collections merged
     *
     * @param self $collection
     *
     * @return self
     */
    public function merge(self $collection): self;

    /**
     * Return a collection containing the wished slice
     *
     * @param int $offset
     * @param int $length
     * @param boolean $preserveKeys
     *
     * @return self
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = false): self;

    /**
     * Return a new collection with the computed diff
     *
     * @param self $collection
     * @param callable $differ
     *
     * @return self
     */
    public function udiff(self $collection, callable $differ): self;

    /**
     * Return a new collection containing the values of the given column
     *
     * @param mixed $key
     * @param mixed $indexKey
     *
     * @return self
     */
    public function column($key, $indexKey = null): self;

    /**
     * Return a new collection with a slice replaced by the given replacement
     *
     * @param int $offset
     * @param int $length
     * @param array $replacement
     *
     * @return self
     */
    public function splice(int $offset, int $length = 0, $replacement = []): self;

    /**
     * Returns a new collection with only unique values
     *
     * @param int $flags
     *
     * @return self
     */
    public function unique(int $flags = self::SORT_REGULAR): self;

    /**
     * Return a new collection with only the values
     *
     * @return self
     */
    public function values(): self;

    /**
     * Return the product of the values
     *
     * @return number
     */
    public function product();

    /**
     * Return a new collection with elements replaced from the given collection
     *
     * @param self $collection
     *
     * @return self
     */
    public function replace(self $collection): self;

    /**
     * Returns a new collection with the values in reversed order
     *
     * @param boolean $preserveKeys
     *
     * @return self
     */
    public function reverse(bool $preserveKeys = false): self;

    /**
     * Return a new collection with the given value at the beginning of it
     *
     * @param mixed $value
     *
     * @return self
     */
    public function unshift($value): self;

    /**
     * Return a new collection containing the diff by keys of the collections
     *
     * @param self $collection
     *
     * @return self
     */
    public function keyDiff(self $collection): self;

    /**
     * Return a new collection containing the diff generated by the differ
     *
     * @param self $collection
     * @param callable $differ
     *
     * @return self
     */
    public function ukeyDiff(self $collection, callable $differ): self;

    /**
     * Returns a new collection with diff applied to both values and keys
     *
     * @param self $collection
     *
     * @return self
     */
    public function associativeDiff(self $collection): self;

    /**
     * Check if a key exist in the collection
     *
     * @param mixed $key
     * @param boolean $strict When strict it uses array_key_exists, otherwise isset
     *
     * @return bool
     */
    public function hasKey($key, bool $strict = true): bool;

    /**
     * Return a new collection with the count of each value
     *
     * @return self
     */
    public function countValues(): self;

    /**
     * Return a new collection intersected by key via the intersecter
     *
     * @param self $collection
     * @param callable $intersecter
     *
     * @return self
     */
    public function ukeyIntersect(self $collection, callable $intersecter): self;

    /**
     * Return a new collection intersected with additional check on keys
     *
     * @param self $collection
     *
     * @return self
     */
    public function associativeIntersect(self $collection): self;

    /**
     * Return a new collection with the sorted values
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function sort(int $flags = self::SORT_REGULAR): self;

    /**
     * Return a new collection with the sorted values and indexes preserved
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function associativeSort(int $flags = self::SORT_REGULAR): self;

    /**
     * Return a new collection sorted by keys
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function keySort(int $flags = self::SORT_REGULAR): self;

    /**
     * Return a new collection sorted by keys via the given sorter
     *
     * @param callable $sorter
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function ukeySort(callable $sorter): self;

    /**
     * Return a new collection sorted in the reversed order
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function reverseSort(int $flags = self::SORT_REGULAR): self;

    /**
     * Return a new collection sorted via the given sorter
     *
     * @param callable $sorter
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function usort(callable $sorter): self;

    /**
     * Return a new collection sorted in the reversed order with preserved keys
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function associativeReverseSort(int $flags = self::SORT_REGULAR): self;

    /**
     * Return a new collection sorted by keys in the reversed order
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function keyReverseSort(int $flags = self::SORT_REGULAR): self;

    /**
     * Return a new collection sorted by the given sorter with preserved keys
     *
     * @param callable $sorter
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function uassociativeSort(callable $sorter): self;

    /**
     * Return a new collection with a natural sort applied to it
     *
     * @throws SortException If the sort failed
     *
     * @return self
     */
    public function naturalSort(): self;

    /**
     * Return the first collection element
     *
     * @throws OutOfBoundException If there is no element
     *
     * @return mixed
     */
    public function first();

    /**
     * Return the last collection element
     *
     * @throws OutOfBoundException If there is no element
     *
     * @return mixed
     */
    public function last();

    /**
     * Run the given callable on each element (no mutation possible)
     *
     * @param Closure $callback
     *
     * @return self
     */
    public function each(\Closure $callback): self;

    /**
     * Concatenate the values into a string separated by the given string
     *
     * @param string $separator
     *
     * @return string
     */
    public function join(string $separator): string;

    /**
     * Return a new collection with the values order shuffled
     *
     * @throws RuntimeException If the operation fails
     *
     * @return self
     */
    public function shuffle(): self;

    /**
     * Return a new collection with n elements took randomly
     *
     * @param int $size
     * @param bool preserveKeys
     *
     * @return self
     */
    public function take(int $size, bool $preserveKeys = false): self;

    /**
     * Return all elements matching the given regex
     *
     * @param string $pattern
     * @param bool $revert
     *
     * @return self
     */
    public function grep(string $pattern, bool $revert = false): self;

    /**
     * Set the element at the specified key
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return self
     */
    public function set($key, $value): self;

    /**
     * Check if the element is in the collection
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($value): bool;
}
