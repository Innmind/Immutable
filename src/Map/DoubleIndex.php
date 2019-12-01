<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
    Type,
    Str,
    Sequence,
    Set,
    Pair,
    ValidateArgument,
    Exception\LogicException,
    Exception\ElementNotFound,
    Exception\CannotGroupEmptyStructure,
};

/**
 * {@inheritdoc}
 */
final class DoubleIndex implements Implementation
{
    private string $keyType;
    private string $valueType;
    private ValidateArgument $validateKey;
    private ValidateArgument $validateValue;
    private Sequence $keys;
    private Sequence $values;
    private Sequence $pairs;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $keyType, string $valueType)
    {
        $this->validateKey = Type::of($keyType);
        $this->validateValue = Type::of($valueType);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->keys = Sequence::of($keyType);
        $this->values = Sequence::of($valueType);
        $this->pairs = Sequence::of(Pair::class);
    }

    /**
     * {@inheritdoc}
     */
    public function keyType(): string
    {
        return $this->keyType;
    }

    /**
     * {@inheritdoc}
     */
    public function valueType(): string
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
    public function put($key, $value): Implementation
    {
        ($this->validateKey)($key, 1);
        ($this->validateValue)($value, 2);

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
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!$this->keys->contains($key)) {
            throw new ElementNotFound($key);
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
    public function clear(): Implementation
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
    public function equals(Implementation $map): bool
    {
        if (!$map->keys()->equals($this->keys())) {
            return false;
        }

        foreach ($this->pairs->toArray() as $pair) {
            if ($map->get($pair->key()) !== $pair->value()) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): Implementation
    {
        $map = $this->clear();

        foreach ($this->pairs->toArray() as $pair) {
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
    public function foreach(callable $function): void
    {
        foreach ($this->pairs->toArray() as $pair) {
            $function($pair->key(), $pair->value());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): Map
    {
        if ($this->size() === 0) {
            throw new CannotGroupEmptyStructure;
        }

        $map = null;

        foreach ($this->pairs->toArray() as $pair) {
            $key = $discriminator($pair->key(), $pair->value());

            if ($map === null) {
                $map = Map::of(
                    Type::determine($key),
                    Map::class
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
                    $this->clearMap()->put(
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
    public function keys(): Set
    {
        return Set::of($this->keyType, ...$this->keys->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function values(): Sequence
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): Implementation
    {
        $map = $this->clear();

        foreach ($this->pairs->toArray() as $pair) {
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
    public function remove($key): Implementation
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
    public function merge(Implementation $map): Implementation
    {
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
    public function partition(callable $predicate): Map
    {
        $truthy = $this->clearMap();
        $falsy = $this->clearMap();

        foreach ($this->pairs->toArray() as $pair) {
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

        return Map::of('bool', Map::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->pairs->toArray() as $pair) {
            $carry = $reducer($carry, $pair->key(), $pair->value());
        }

        return $carry;
    }

    public function empty(): bool
    {
        return $this->pairs->empty();
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of($this->keyType, $this->valueType);
    }
}
