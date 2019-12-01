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
final class Primitive implements Implementation
{
    private string $keyType;
    private string $valueType;
    private ValidateArgument $validateKey;
    private ValidateArgument $validateValue;
    private array $values;
    private ?int $size;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $keyType, string $valueType)
    {
        $this->validateKey = Type::of($keyType);

        if (!in_array($keyType, ['int', 'integer', 'string'], true)) {
            throw new LogicException;
        }

        $this->validateValue = Type::of($valueType);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->values = [];
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
    public function put($key, $value): Implementation
    {
        ($this->validateKey)($key, 1);
        ($this->validateValue)($value, 2);

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
            throw new ElementNotFound;
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
    public function clear(): Implementation
    {
        $map = clone $this;
        $map->size = null;
        $map->values = [];

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(Implementation $map): bool
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
    public function filter(callable $predicate): Implementation
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
    public function foreach(callable $function): void
    {
        foreach ($this->values as $k => $v) {
            $function($this->normalizeKey($k), $v);
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

        foreach ($this->values as $k => $v) {
            $key = $discriminator($this->normalizeKey($k), $v);

            if ($map === null) {
                $map = Map::of(
                    Type::determine($key),
                    Map::class
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
                    $this->clearMap()->put($k, $v)
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
        return Set::of(
            $this->keyType,
            ...\array_map(function($key) {
                return $this->normalizeKey($key);
            }, \array_keys($this->values))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function values(): Sequence
    {
        return Sequence::of($this->valueType, ...\array_values($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): Implementation
    {
        $map = $this->clear();

        foreach ($this->values as $k => $v) {
            $return = $function($this->normalizeKey($k), $v);

            if ($return instanceof Pair) {
                ($this->validateKey)($return->key(), 1);

                $key = $return->key();
                $value = $return->value();
            } else {
                $key = $k;
                $value = $return;
            }

            ($this->validateValue)($value, 2);

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
    public function remove($key): Implementation
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

        foreach ($this->values as $k => $v) {
            $return = $predicate($this->normalizeKey($k), $v);

            if ($return === true) {
                $truthy = $truthy->put($k, $v);
            } else {
                $falsy = $falsy->put($k, $v);
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
        if ($this->keyType === 'string' && !\is_null($value)) {
            return (string) $value;
        }

        return $value;
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of($this->keyType, $this->valueType);
    }
}
