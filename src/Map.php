<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Specification\ClassType,
    Exception\InvalidArgumentException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};

/**
 * @template T
 * @template S
 */
final class Map implements \Countable
{
    private Map\Implementation $implementation;

    public function __construct(string $keyType, string $valueType)
    {
        $type = Type::of($keyType);

        if ($type instanceof ClassType || $keyType === 'object') {
            $this->implementation = new Map\ObjectKeys($keyType, $valueType);
        } else if (\in_array($keyType, ['int', 'integer', 'string'], true)) {
            $this->implementation = new Map\Primitive($keyType, $valueType);
        } else {
            $this->implementation = new Map\DoubleIndex($keyType, $valueType);
        }
    }

    public static function of(
        string $key,
        string $value,
        array $keys = [],
        array $values = []
    ): self {
        $keys = \array_values($keys);
        $values = \array_values($values);

        if (\count($keys) !== \count($values)) {
            throw new LogicException('Different sizes of keys and values');
        }

        $self = new self($key, $value);

        foreach ($keys as $i => $key) {
            $self = $self->put($key, $values[$i]);
        }

        return $self;
    }

    /**
     * Return the key type for this map
     */
    public function keyType(): Str
    {
        return $this->implementation->keyType();
    }

    /**
     * Return the value type for this map
     */
    public function valueType(): Str
    {
        return $this->implementation->valueType();
    }

    public function size(): int
    {
        return $this->implementation->size();
    }

    /**
     * {@inheritdoc}
     */
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
        $map = clone $this;
        $map->implementation = $this->implementation->put($key, $value);

        return $map;
    }

    /**
     * Alias for put method in order to have a syntax similar to a true tuple
     * when constructing the map
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
        return $this->put($key, $value);
    }

    /**
     * Return the element with the given key
     *
     * @param T $key
     *
     * @throws ElementNotFoundException
     *
     * @return S
     */
    public function get($key)
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
     *
     * @return self<T, S>
     */
    public function foreach(callable $function): self
    {
        $this->implementation->foreach($function);

        return $this;
    }

    /**
     * Return a new map of pairs' sequences grouped by keys determined with the given
     * discriminator function
     *
     * @param callable(T, S) $discriminator
     *
     * @return self<mixed, self<T, S>>
     */
    public function groupBy(callable $discriminator): self
    {
        return $this->implementation->groupBy($discriminator);
    }

    /**
     * Return all keys
     *
     * @return SetInterface<T>
     */
    public function keys(): SetInterface
    {
        return $this->implementation->keys();
    }

    /**
     * Return all values
     *
     * @return StreamInterface<S>
     */
    public function values(): StreamInterface
    {
        return $this->implementation->values();
    }

    /**
     * Apply the given function on all elements and return a new map
     *
     * Keys can't be modified
     *
     * @param callable(T, S): S|Pair<T, S> $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self
    {
        $map = $this->clear();
        $map->implementation = $this->implementation->map($function);

        return $map;
    }

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str
    {
        return $this->implementation->join($separator);
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
     * @param mixed $carry
     * @param callable(mixed, T, S) $reducer
     *
     * @return mixed
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->implementation->reduce($carry, $reducer);
    }

    public function empty(): bool
    {
        return $this->implementation->empty();
    }
}
