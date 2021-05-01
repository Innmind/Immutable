<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @template T
 * @template S
 */
final class Map implements \Countable
{
    private Map\Implementation $implementation;

    private function __construct(Map\Implementation $implementation)
    {
        $this->implementation = $implementation;
    }

    /**
     * Set a new key/value pair
     *
     * Example:
     * <code>
     * Map::of('int', 'int')
     *     (1, 2)
     *     (3, 4)
     * </code>
     *
     * @param T $key
     * @param S $value
     *
     * @return self<T, S>
     */
    public function __invoke($key, $value): self
    {
        $map = clone $this;
        $map->implementation = ($this->implementation)($key, $value);

        return $map;
    }

    /**
     * @template U
     * @template V
     *
     * @return self<U, V>
     */
    public static function of(): self
    {
        return new self(new Map\Uninitialized);
    }

    public function size(): int
    {
        return $this->implementation->size();
    }

    public function count(): int
    {
        return $this->implementation->count();
    }

    /**
     * Set a new key/value pair
     *
     * @param T $key
     * @param S $value
     *
     * @return self<T, S>
     */
    public function put($key, $value): self
    {
        return ($this)($key, $value);
    }

    /**
     * Return the element with the given key
     *
     * @param T $key
     *
     * @return Maybe<S>
     */
    public function get($key): Maybe
    {
        return $this->implementation->get($key);
    }

    /**
     * Check if there is an element for the given key
     *
     * @param T $key
     */
    public function contains($key): bool
    {
        return $this->implementation->contains($key);
    }

    /**
     * Return an empty map given the same given type
     *
     * @return self<T, S>
     */
    public function clear(): self
    {
        $map = clone $this;
        $map->implementation = $this->implementation->clear();

        return $map;
    }

    /**
     * Check if the two maps are equal
     *
     * @param self<T, S> $map
     */
    public function equals(self $map): bool
    {
        return $this->implementation->equals($map->implementation);
    }

    /**
     * Filter the map based on the given predicate
     *
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    public function filter(callable $predicate): self
    {
        $map = $this->clear();
        $map->implementation = $this->implementation->filter($predicate);

        return $map;
    }

    /**
     * Run the given function for each element of the map
     *
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): void
    {
        $this->implementation->foreach($function);
    }

    /**
     * Return a new map of pairs' sequences grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     *
     * @param callable(T, S): D $discriminator
     *
     * @return self<D, self<T, S>>
     */
    public function groupBy(callable $discriminator): self
    {
        return $this->implementation->groupBy($discriminator);
    }

    /**
     * Return all keys
     *
     * @return Set<T>
     */
    public function keys(): Set
    {
        return $this->implementation->keys();
    }

    /**
     * Return all values
     *
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this->implementation->values();
    }

    /**
     * Apply the given function on all elements and return a new map
     *
     * @template B
     *
     * @param callable(T, S): B $function
     *
     * @return self<T, B>
     */
    public function map(callable $function): self
    {
        return new self($this->implementation->map($function));
    }

    /**
     * Merge all Maps created by by each value from the initial Map
     *
     * @template A
     * @template B
     *
     * @param callable(T, S): self<A, B> $map
     *
     * @return self<A, B>
     */
    public function flatMap(callable $map): self
    {
        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress MixedArgument
         */
        return $this->reduce(
            self::of(),
            static fn(self $carry, $key, $value) => $carry->merge($map($key, $value)),
        );
    }

    /**
     * Remove the element with the given key
     *
     * @param T $key
     *
     * @return self<T, S>
     */
    public function remove($key): self
    {
        $map = clone $this;
        $map->implementation = $this->implementation->remove($key);

        return $map;
    }

    /**
     * Create a new map by combining both maps
     *
     * @param self<T, S> $map
     *
     * @return self<T, S>
     */
    public function merge(self $map): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->merge($map->implementation);

        return $self;
    }

    /**
     * Return a map of 2 maps partitioned according to the given predicate
     *
     * @param callable(T, S): bool $predicate
     *
     * @return self<bool, self<T, S>>
     */
    public function partition(callable $predicate): self
    {
        return $this->implementation->partition($predicate);
    }

    /**
     * Reduce the map to a single value
     *
     * @template R
     * @param R $carry
     * @param callable(R, T, S): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->implementation->reduce($carry, $reducer);
    }

    public function empty(): bool
    {
        return $this->implementation->empty();
    }

    /**
     * @param callable(T, S): bool $predicate
     */
    public function matches(callable $predicate): bool
    {
        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MissingClosureParamType
         */
        return $this->reduce(
            true,
            static fn(bool $matches, $key, $value): bool => $matches && $predicate($key, $value),
        );
    }

    /**
     * @param callable(T, S): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress MissingClosureParamType
         */
        return $this->reduce(
            false,
            static fn(bool $any, $key, $value): bool => $any || $predicate($key, $value),
        );
    }
}
