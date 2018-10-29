<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Exception\InvalidArgumentException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};

final class Map implements MapInterface
{
    use Type;

    private $keyType;
    private $valueType;
    private $keySpecification;
    private $valueSpecification;
    private $keys;
    private $values;
    private $pairs;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $keyType, string $valueType)
    {
        $this->keySpecification = $this->getSpecificationFor($keyType);
        $this->valueSpecification = $this->getSpecificationFor($valueType);
        $this->keyType = new Str($keyType);
        $this->valueType = new Str($valueType);
        $this->keys = new Stream($keyType);
        $this->values = new Stream($valueType);
        $this->pairs = new Stream(Pair::class);
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
        return $this->keyType;
    }

    /**
     * {@inheritdoc}
     */
    public function valueType(): Str
    {
        return $this->valueType;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->keys->size();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->keys->count();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->values->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->keys->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->keys->next();
        $this->values->next();
        $this->pairs->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->keys->rewind();
        $this->values->rewind();
        $this->pairs->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->keys->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->keys->contains($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('You can\'t modify a map');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('You can\'t modify a map');
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $value): MapInterface
    {
        $this->keySpecification->validate($key);
        $this->valueSpecification->validate($value);

        $map = clone $this;

        if ($this->keys->contains($key)) {
            $index = $this->keys->indexOf($key);
            $map->values = $this->values->take($index)
                ->add($value)
                ->append($this->values->drop($index + 1));
            $map->pairs = $this->pairs->take($index)
                ->add(new Pair($key, $value))
                ->append($this->pairs->drop($index + 1));
        } else {
            $map->keys = $this->keys->add($key);
            $map->values = $this->values->add($value);
            $map->pairs = $this->pairs->add(new Pair($key, $value));
        }

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
        if (!$this->keys->contains($key)) {
            throw new ElementNotFoundException;
        }

        return $this->values->get(
            $this->keys->indexOf($key)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key): bool
    {
        return $this->keys->contains($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): MapInterface
    {
        $map = clone $this;
        $map->keys = $this->keys->clear();
        $map->values = $this->values->clear();
        $map->pairs = $this->pairs->clear();

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(MapInterface $map): bool
    {
        if (!$map->keys()->equals($this->keys())) {
            return false;
        }

        foreach ($this->pairs as $pair) {
            if ($map->get($pair->key()) !== $pair->value()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): MapInterface
    {
        $map = $this->clear();

        foreach ($this->pairs as $pair) {
            if ($predicate($pair->key(), $pair->value()) === true) {
                $map->keys = $map->keys->add($pair->key());
                $map->values = $map->values->add($pair->value());
                $map->pairs = $map->pairs->add($pair);
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): MapInterface
    {
        foreach ($this->pairs as $pair) {
            $function($pair->key(), $pair->value());
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        if ($this->size() === 0) {
            throw new GroupEmptyMapException;
        }

        $map = null;

        foreach ($this->pairs as $pair) {
            $key = $discriminator($pair->key(), $pair->value());

            if ($map === null) {
                $map = new self(
                    $this->determineType($key),
                    MapInterface::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->put(
                        $pair->key(),
                        $pair->value()
                    )
                );
            } else {
                $map = $map->put(
                    $key,
                    $this->clear()->put(
                        $pair->key(),
                        $pair->value()
                    )
                );
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): SetInterface
    {
        return Set::of((string) $this->keyType, ...$this->keys);
    }

    /**
     * {@inheritdoc}
     */
    public function values(): StreamInterface
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): MapInterface
    {
        $map = $this->clear();

        foreach ($this->pairs as $pair) {
            $return = $function(
                $pair->key(),
                $pair->value()
            );

            if ($return instanceof Pair) {
                $key = $return->key();
                $value = $return->value();
            } else {
                $key = $pair->key();
                $value = $return;
            }

            $map = $map->put($key, $value);
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->values->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key): MapInterface
    {
        if (!$this->contains($key)) {
            return $this;
        }

        $index = $this->keys->indexOf($key);
        $map = clone $this;
        $map->keys = $this
            ->keys
            ->slice(0, $index)
            ->append($this->keys->slice($index + 1, $this->keys->size()));
        $map->values = $this
            ->values
            ->slice(0, $index)
            ->append($this->values->slice($index + 1, $this->values->size()));
        $map->pairs = $this
            ->pairs
            ->slice(0, $index)
            ->append($this->pairs->slice($index + 1, $this->pairs->size()));

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(MapInterface $map): MapInterface
    {
        if (
            !$this->keyType()->equals($map->keyType()) ||
            !$this->valueType()->equals($map->valueType())
        ) {
            throw new InvalidArgumentException(
                'The 2 maps does not reference the same types'
            );
        }

        return $map->reduce(
            $this,
            function(self $carry, $key, $value): self {
                return $carry->put($key, $value);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        $truthy = $this->clear();
        $falsy = $this->clear();

        foreach ($this->pairs as $pair) {
            $return = $predicate(
                $pair->key(),
                $pair->value()
            );

            if ($return === true) {
                $truthy = $truthy->put($pair->key(), $pair->value());
            } else {
                $falsy = $falsy->put($pair->key(), $pair->value());
            }
        }

        return (new self('bool', MapInterface::class))
            ->put(true, $truthy)
            ->put(false, $falsy);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->pairs as $pair) {
            $carry = $reducer($carry, $pair->key(), $pair->value());
        }

        return $carry;
    }
}
