<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    MapInterface,
    Map,
    Type,
    Str,
    Stream,
    StreamInterface,
    SetInterface,
    Set,
    Pair,
    Exception\InvalidArgumentException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};

/**
 * {@inheritdoc}
 */
final class Primitive implements MapInterface
{
    private $keyType;
    private $valueType;
    private $keySpecification;
    private $valueSpecification;
    private $values;
    private $size;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $keyType, string $valueType)
    {
        $this->keySpecification = Type::of($keyType);

        if (!in_array($keyType, ['int', 'integer', 'string'], true)) {
            throw new LogicException;
        }

        $this->valueSpecification = Type::of($valueType);
        $this->keyType = new Str($keyType);
        $this->valueType = new Str($valueType);
        $this->values = [];
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
        return $this->size ?? $this->size = \count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->size();
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $value): MapInterface
    {
        $this->keySpecification->validate($key);
        $this->valueSpecification->validate($value);

        $map = clone $this;
        $map->size = null;
        $map->values[$key] = $value;
        \reset($map->values);

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!$this->contains($key)) {
            throw new ElementNotFoundException;
        }

        return $this->values[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key): bool
    {
        return \array_key_exists($key, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): MapInterface
    {
        $map = clone $this;
        $map->size = null;
        $map->values = [];

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(MapInterface $map): bool
    {
        if ($map->size() !== $this->size()) {
            return false;
        }

        foreach ($this->values as $k => $v) {
            if (!$map->contains($k)) {
                return false;
            }

            if ($map->get($k) !== $v) {
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

        foreach ($this->values as $k => $v) {
            if ($predicate($this->normalizeKey($k), $v) === true) {
                $map->values[$k] = $v;
            }
        }

        \reset($map->values);

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): MapInterface
    {
        foreach ($this->values as $k => $v) {
            $function($this->normalizeKey($k), $v);
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

        foreach ($this->values as $k => $v) {
            $key = $discriminator($this->normalizeKey($k), $v);

            if ($map === null) {
                $map = new Map(
                    Type::determine($key),
                    MapInterface::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->put($k, $v)
                );
            } else {
                $map = $map->put(
                    $key,
                    $this->clear()->put($k, $v)
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
        return Set::of(
            (string) $this->keyType,
            ...\array_map(function($key) {
                return $this->normalizeKey($key);
            }, \array_keys($this->values))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function values(): StreamInterface
    {
        return Stream::of((string) $this->valueType, ...\array_values($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): MapInterface
    {
        $map = $this->clear();

        foreach ($this->values as $k => $v) {
            $return = $function($this->normalizeKey($k), $v);

            if ($return instanceof Pair) {
                $this->keySpecification->validate($return->key());

                $key = $return->key();
                $value = $return->value();
            } else {
                $key = $k;
                $value = $return;
            }

            $this->valueSpecification->validate($value);

            $map->values[$key] = $value;
        }

        \reset($map->values);

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->values()->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key): MapInterface
    {
        if (!$this->contains($key)) {
            return $this;
        }

        $map = clone $this;
        $map->size = null;
        unset($map->values[$key]);
        \reset($map->values);

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

        foreach ($this->values as $k => $v) {
            $return = $predicate($this->normalizeKey($k), $v);

            if ($return === true) {
                $truthy->values[$k] = $v;
            } else {
                $falsy->values[$k] = $v;
            }
        }

        \reset($truthy->values);
        \reset($falsy->values);

        return Map::of('bool', MapInterface::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->values as $k => $v) {
            $carry = $reducer($carry, $this->normalizeKey($k), $v);
        }

        return $carry;
    }

    public function empty(): bool
    {
        \reset($this->values);

        return \is_null(\key($this->values));
    }

    private function normalizeKey($value)
    {
        if ((string) $this->keyType === 'string' && !\is_null($value)) {
            return (string) $value;
        }

        return $value;
    }
}
