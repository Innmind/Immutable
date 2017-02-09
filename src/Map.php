<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Exception\InvalidArgumentException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};

class Map implements MapInterface
{
    use Type;

    private $keyType;
    private $valueType;
    private $keySpec;
    private $valueSpec;
    private $keys;
    private $values;
    private $pairs;

    public function __construct(string $keyType, string $valueType)
    {
        $this->keySpec = $this->getSpecFor($keyType);
        $this->valueSpec = $this->getSpecFor($valueType);
        $this->keyType = new StringPrimitive($keyType);
        $this->valueType = new StringPrimitive($valueType);
        $this->keys = new Sequence;
        $this->values = new Sequence;
        $this->pairs = new Sequence;
    }

    /**
     * {@inheritdoc}
     */
    public function keyType(): StringPrimitive
    {
        return $this->keyType;
    }

    /**
     * {@inheritdoc}
     */
    public function valueType(): StringPrimitive
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
        $this->keySpec->validate($key);
        $this->valueSpec->validate($value);

        $map = clone $this;

        if ($this->keys->contains($key)) {
            $index = $this->keys->indexOf($key);
            $map->values = (new Sequence)
                ->append($this->values->take($index))
                ->add($value)
                ->append($this->values->drop($index + 1));
            $map->pairs = (new Sequence)
                ->append($this->pairs->take($index))
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
        $map->keys = new Sequence;
        $map->values = new Sequence;
        $map->pairs = new Sequence;

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

        return $map->values()->equals($this->values());
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
                $type = gettype($key);
                $map = new self(
                    $type === 'object' ? get_class($key) : $type,
                    SequenceInterface::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->add($pair)
                );
            } else {
                $map = $map->put($key, new Sequence($pair));
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): SequenceInterface
    {
        return $this->keys;
    }

    /**
     * {@inheritdoc}
     */
    public function values(): SequenceInterface
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
    public function join(string $separator): StringPrimitive
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
            clone $this,
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
