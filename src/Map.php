<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    ValidateArgument\ClassType,
    Exception\LogicException,
    Exception\ElementNotFound,
    Exception\CannotGroupEmptyStructure,
};

/**
 * @template T
 * @template S
 * @psalm-immutable
 */
final class Map implements \Countable
{
    private Map\Implementation $implementation;
    private string $keyType;
    private string $valueType;
    private ValidateArgument $validateKey;
    private ValidateArgument $validateValue;

    private function __construct(
        string $keyType,
        string $valueType,
        Map\Implementation $implementation
    ) {
        $type = Type::of($keyType);
        $this->implementation = $implementation;
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->validateKey = Type::of($keyType);
        $this->validateValue = Type::of($valueType);
    }

    /**
     * @template U
     * @template V
     * @psalm-pure
     *
     * @return self<U, V>
     */
    public static function of(string $key, string $value): self
    {
        $type = Type::of($key);

        if ($type instanceof ClassType || $key === 'object') {
            $implementation = new Map\ObjectKeys($key, $value);
        } else if (\in_array($key, ['int', 'integer', 'string'], true)) {
            $implementation = new Map\Primitive($key, $value);
        } else {
            $implementation = new Map\DoubleIndex($key, $value);
        }

        return new self($key, $value, $implementation);
    }

    public function isOfType(string $key, string $value): bool
    {
        return $this->keyType === $key && $this->valueType === $value;
    }

    /**
     * Return the key type for this map
     */
    public function keyType(): string
    {
        return $this->implementation->keyType();
    }

    /**
     * Return the value type for this map
     */
    public function valueType(): string
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
        ($this->validateKey)($key, 1);
        ($this->validateValue)($value, 2);

        $map = clone $this;
        $map->implementation = ($this->implementation)($key, $value);

        return $map;
    }

    /**
     * Return the element with the given key
     *
     * @param T $key
     *
     * @throws ElementNotFound
     *
     * @return S
     */
    public function get($key)
    {
        ($this->validateKey)($key, 1);

        /** @var S */
        return $this->implementation->get($key);
    }

    /**
     * Check if there is an element for the given key
     *
     * @param T $key
     */
    public function contains($key): bool
    {
        ($this->validateKey)($key, 1);

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
        assertMap($this->keyType, $this->valueType, $map, 1);

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
        $map = clone $this;
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
     * @param callable(T, S): D $discriminator
     *
     * @throws CannotGroupEmptyStructure
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
     * Keys can't be modified
     *
     * @param callable(T, S): (S|Pair<T, S>) $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self
    {
        $map = clone $this;
        $map->implementation = $this->implementation->map($function);

        return $map;
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
        ($this->validateKey)($key, 1);

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
        assertMap($this->keyType, $this->valueType, $map, 1);

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
     * @template ST
     *
     * @param callable(T, S): \Generator<ST> $mapper
     *
     * @return Sequence<ST>
     */
    public function toSequenceOf(string $type, callable $mapper): Sequence
    {
        return $this->implementation->toSequenceOf($type, $mapper);
    }

    /**
     * @template ST
     *
     * @param callable(T, S): \Generator<ST> $mapper
     *
     * @return Set<ST>
     */
    public function toSetOf(string $type, callable $mapper): Set
    {
        return $this->implementation->toSetOf($type, $mapper);
    }

    /**
     * @template MT
     * @template MS
     *
     * @param null|callable(T, S): \Generator<MT, MS> $mapper
     *
     * @return self<MT, MS>
     */
    public function toMapOf(string $key, string $value, callable $mapper = null): self
    {
        return $this->implementation->toMapOf($key, $value, $mapper);
    }
}
