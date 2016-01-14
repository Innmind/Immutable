<?php

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
     * @return CollectionInterface
     */
    public function filter(callable $filter = null);

    /**
     * Returns a new collection which contains the intersection of both collections
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function intersect(CollectionInterface $collection);

    /**
     * Split the collection into a collection of collections of the given size
     *
     * @param int $size
     *
     * @return CollectionInterface
     */
    public function chunk($size);

    /**
     * Returns a new collection without the first element of the current one
     *
     * @return CollectionInterface
     */
    public function shift();

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
    public function search($needle, $strict = true);

    /**
     * Returns a new collection of the collections' intersection given the intersecter
     *
     * @param CollectionInterface $collection
     * @param callable $intersecter
     *
     * @return CollectionInterface
     */
    public function uintersect(CollectionInterface $collection, callable $intersecter);

    /**
     * Returns a new collection of the collections' intersection via keys
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function keyIntersect(CollectionInterface $collection);

    /**
     * Return a new collection with each value transformed with the given mapper
     *
     * @param callable $mapper
     *
     * @return CollectionInterface
     */
    public function map(callable $mapper);

    /**
     * Returns a new collection with each value padded to the given size
     *
     * @param int $size
     * @param mixed $value
     *
     * @return CollectionInterface
     */
    public function pad($size, $value);

    /**
     * Returns a new collection without the last element of the current one
     *
     * @return CollectionInterface
     */
    public function pop();

    /**
     * Sum all the values of the collection
     *
     * @return number
     */
    public function sum();

    /**
     * Returns a new collection containing the difference of each collections
     *
     * @param CollectionInterface $collections
     *
     * @return CollectionInterface
     */
    public function diff(CollectionInterface $collections);

    /**
     * Return a new collection with the keys and values flipped
     *
     * @return CollectionInterface
     */
    public function flip();

    /**
     * Return a new collection containing the current one keys
     *
     * @param mixed $search
     * @param boolean $strict
     *
     * @return CollectionInterface
     */
    public function keys($search = null, $strict = true);

    /**
     * Returns a new collection with the given value added at the end
     *
     * @param mixed $value
     *
     * @return CollectionInterface
     */
    public function push($value);

    /**
     * Return a new collection containing one or more keys of the randomly picked values
     *
     * @param int $num
     *
     * @throws OutOfBoundException If the num is higher than the collection length
     *
     * @return CollectionInterface
     */
    public function rand($num = 1);

    /**
     * Returns a new collection with both collections merged
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function merge(CollectionInterface $collection);

    /**
     * Return a collection containing the wished slice
     *
     * @param int $offset
     * @param int $length
     * @param boolean $preserveKeys
     *
     * @return CollectionInterface
     */
    public function slice($offset, $length = null, $preserveKeys = false);

    /**
     * Return a new collection with the computed diff
     *
     * @param CollectionInterface $collection
     * @param callable $differ
     *
     * @return CollectionInterface
     */
    public function udiff(CollectionInterface $collection, callable $differ);

    /**
     * Return a new collection containing the values of the given column
     *
     * @param mixed $key
     * @param mixed $indexKey
     *
     * @return CollectionInterface
     */
    public function column($key, $indexKey = null);

    /**
     * Return a new collection with a slice replaced by the given replacement
     *
     * @param int $offset
     * @param int $length
     * @param array $replacement
     *
     * @return CollectionInterface
     */
    public function splice($offset, $length = 0, $replacement = []);

    /**
     * Returns a new collection with only unique values
     *
     * @param int $flags
     *
     * @return CollectionInterface
     */
    public function unique($flags = self::SORT_REGULAR);

    /**
     * Return a new collection with only the values
     *
     * @return CollectionInterface
     */
    public function values();

    /**
     * Return the product of the values
     *
     * @return number
     */
    public function product();

    /**
     * Return a new collection with elements replaced from the given collection
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function replace(CollectionInterface $collection);

    /**
     * Returns a new collection with the values in reversed order
     *
     * @param boolean $preserveKeys
     *
     * @return CollectionInterface
     */
    public function reverse($preserveKeys = false);

    /**
     * Return a new collection with the given value at the beginning of it
     *
     * @param mixed $value
     *
     * @return CollectionInterface
     */
    public function unshift($value);

    /**
     * Return a new collection containing the diff by keys of the collections
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function keyDiff(CollectionInterface $collection);

    /**
     * Return a new collection containing the diff generated by the differ
     *
     * @param CollectionInterface $collection
     * @param callable $differ
     *
     * @return CollectionInterface
     */
    public function ukeyDiff(CollectionInterface $collection, callable $differ);

    /**
     * Returns a new collection with diff applied to both values and keys
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function associativeDiff(CollectionInterface $collection);

    /**
     * Check if a key exist in the collection
     *
     * @param mixed $key
     * @param boolean $strict When strict it uses array_key_exists, otherwise isset
     *
     * @return bool
     */
    public function hasKey($key, $strict = true);

    /**
     * Return a new collection with the count of each value
     *
     * @return CollectionInterface
     */
    public function countValues();

    /**
     * Return a new collection intersected by key via the intersecter
     *
     * @param CollectionInterface $collection
     * @param callable $intersecter
     *
     * @return CollectionInterface
     */
    public function ukeyIntersect(CollectionInterface $collection, callable $intersecter);

    /**
     * Return a new collection intersected with additional check on keys
     *
     * @param CollectionInterface $collection
     *
     * @return CollectionInterface
     */
    public function associativeIntersect(CollectionInterface $collection);

    /**
     * Return a new collection with the sorted values
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function sort($flags = self::SORT_REGULAR);

    /**
     * Return a new collection with the sorted values and indexes preserved
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function associativeSort($flags = self::SORT_REGULAR);

    /**
     * Return a new collection sorted by keys
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function keySort($flags = self::SORT_REGULAR);

    /**
     * Return a new collection sorted by keys via the given sorter
     *
     * @param callable $sorter
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function ukeySort(callable $sorter);

    /**
     * Return a new collection sorted in the reversed order
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function reverseSort($flags = self::SORT_REGULAR);

    /**
     * Return a new collection sorted via the given sorter
     *
     * @param callable $sorter
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function usort(callable $sorter);

    /**
     * Return a new collection sorted in the reversed order with preserved keys
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function associativeReverseSort($flags = self::SORT_REGULAR);

    /**
     * Return a new collection sorted by keys in the reversed order
     *
     * @param int $flags
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function keyReverseSort($flags = self::SORT_REGULAR);

    /**
     * Return a new collection sorted by the given sorter with preserved keys
     *
     * @param callable $sorter
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function uassociativeSort(callable $sorter);

    /**
     * Return a new collection with a natural sort applied to it
     *
     * @throws SortException If the sort failed
     *
     * @return CollectionInterface
     */
    public function naturalSort();

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
     * @param callable $callback
     *
     * @return CollectionInterface self
     */
    public function each(callable $callback);

    /**
     * Concatenate the values into a string separated by the given string
     *
     * @param string $separator
     *
     * @return string
     */
    public function join($separator);

    /**
     * Return a new collection with the values order shuffled
     *
     * @throws RuntimeException If the operation fails
     *
     * @return CollectionInterface
     */
    public function shuffle();

    /**
     * Return a new collection with n elements took randomly
     *
     * @param int $size
     * @param bool preserveKeys
     *
     * @return CollectionInterface
     */
    public function take($size, $preserveKeys = false);

    /**
     * Return all elements matching the given regex
     *
     * @param string $pattern
     * @param bool $revert
     *
     * @return CollectionInterface
     */
    public function grep($pattern, $revert = false);

    /**
     * Set the element at the specified key
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return CollectionInterface
     */
    public function set($key, $value);
}
