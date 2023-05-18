<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @template-covariant T
 * @template-covariant S
 * @psalm-immutable
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
     * Map::of()
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
        return new self(($this->implementation)($key, $value));
    }

    /**
     * @template U
     * @template V
     * @psalm-pure
     *
     * @param list<array{0: U, 1: V}> $pairs
     *
     * @return self<U, V>
     */
    public static function of(array ...$pairs): self
    {
        $self = new self(new Map\Uninitialized);

        foreach ($pairs as [$key, $value]) {
            $self = ($self)($key, $value);
        }

        return $self;
    }

    /**
     * @return 0|positive-int
     */
    public function size(): int
    {
        return $this->implementation->size();
    }

    /**
     * @return 0|positive-int
     */
    public function count(): int
    {
        return $this->size();
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
     * Return an empty map of the same type
     *
     * @return self<T, S>
     */
    public function clear(): self
    {
        return new self($this->implementation->clear());
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
        return new self($this->implementation->filter($predicate));
    }

    /**
     * Exclude elements that match the predicate
     *
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    public function exclude(callable $predicate): self
    {
        /** @psalm-suppress MixedArgument */
        return $this->filter(static fn($key, $value) => !$predicate($key, $value));
    }

    /**
     * Run the given function for each element of the map
     *
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->implementation->foreach($function);
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
     * Merge all Maps created by each value from the initial Map
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
        /** @var self<A, B> */
        $all = self::of();

        /**
         * @psalm-suppress InvalidArgument
         * @psalm-suppress MixedArgument
         */
        return $this->reduce(
            $all,
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
        return new self($this->implementation->remove($key));
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
        return new self($this->implementation->merge($map->implementation));
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
     * @template I
     * @template R
     *
     * @param I $carry
     * @param callable(I|R, T, S): R $reducer
     *
     * @return I|R
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
     *
     * @return Maybe<Pair<T, S>>
     */
    public function find(callable $predicate): Maybe
    {
        return $this->implementation->find($predicate);
    }

    /**
     * @param callable(T, S): bool $predicate
     */
    public function matches(callable $predicate): bool
    {
        /** @psalm-suppress MixedArgument For some reason Psalm no longer recognize the type in `find` */
        return $this
            ->find(static fn($key, $value) => !$predicate($key, $value))
            ->match(
                static fn() => false,
                static fn() => true,
            );
    }

    /**
     * @param callable(T, S): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        return $this->find($predicate)->match(
            static fn() => true,
            static fn() => false,
        );
    }
}
