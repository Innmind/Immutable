<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface MapInterface extends SizeableInterface, \Countable, \Iterator, \ArrayAccess
{
    public function __construct(string $keyType, string $valueType);

    /**
     * Return the key type for this map
     *
     * @return string
     */
    public function keyType(): string;

    /**
     * Return the value type for this map
     *
     * @return string
     */
    public function valueType(): string;

    /**
     * Set a new key/value pair
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return self
     */
    public function put($key, $value): self;

    /**
     * Return the element with the given key
     *
     * @param mixed $key
     *
     * @throws ElementNotFoundException
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Check if there is an element for the given key
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function contains($key): bool;

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
     * Return an empty map given the same given type
     *
     * @return self
     */
    public function clear(): self;

    /**
     * Check if the two maps are equal
     *
     * @param self $map
     *
     * @return bool
     */
    public function equals(self $map): bool;

    /**
     * Filter the map based on the given predicate
     *
     * @param Closure $predicate
     *
     * @return self
     */
    public function filter(\Closure $predicate): self;

    /**
     * Run the given function for each element of the map
     *
     * @param Closure $function
     *
     * @return self
     */
    public function foreach(\Closure $function): self;

    /**
     * Return a new map of pairs' sequences grouped by keys determined with the given
     * discriminator function
     *
     * @param Closure $discriminator
     *
     * @return self
     */
    public function groupBy(\Closure $discriminator): self;

    /**
     * Return the first element
     *
     * @return Pair
     */
    public function first(): Pair;

    /**
     * Return the last element
     *
     * @return Pair
     */
    public function last(): Pair;

    /**
     * Return all keys
     *
     * @return SequenceInterface
     */
    public function keys(): SequenceInterface;

    /**
     * Return all values
     *
     * @return SequenceInterface
     */
    public function values(): SequenceInterface;

    /**
     * Apply the given function on all elements and return a new map
     *
     * Keys can't be modified
     *
     * @param Closure $function
     *
     * @return self
     */
    public function map(\Closure $function): self;

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
     * Concatenate all elements with the given separator
     *
     * @param string $separator
     *
     * @return StringPrimitive
     */
    public function join(string $separator): StringPrimitive;
}
