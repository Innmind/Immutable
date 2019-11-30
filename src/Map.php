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
 * {@inheritdoc}
 */
final class Map implements MapInterface
{
    private MapInterface $implementation;

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function keyType(): Str
    {
        return $this->implementation->keyType();
    }

    /**
     * {@inheritdoc}
     */
    public function valueType(): Str
    {
        return $this->implementation->valueType();
    }

    /**
     * {@inheritdoc}
     */
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
     * {@inheritdoc}
     */
    public function put($key, $value): MapInterface
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
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->implementation->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key): bool
    {
        return $this->implementation->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): MapInterface
    {
        $map = clone $this;
        $map->implementation = $this->implementation->clear();

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(MapInterface $map): bool
    {
        return $this->implementation->equals($map);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): MapInterface
    {
        $map = $this->clear();
        $map->implementation = $this->implementation->filter($predicate);

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): MapInterface
    {
        $this->implementation->foreach($function);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        return $this->implementation->groupBy($discriminator);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): SetInterface
    {
        return $this->implementation->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function values(): StreamInterface
    {
        return $this->implementation->values();
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): MapInterface
    {
        $map = $this->clear();
        $map->implementation = $this->implementation->map($function);

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->implementation->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key): MapInterface
    {
        $map = clone $this;
        $map->implementation = $this->implementation->remove($key);

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(MapInterface $map): MapInterface
    {
        $self = clone $this;
        $self->implementation = $this->implementation->merge($map);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        return $this->implementation->partition($predicate);
    }

    /**
     * {@inheritdoc}
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
