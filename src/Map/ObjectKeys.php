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
    ValidateArgument\ClassType,
    Exception\LogicException,
    Exception\ElementNotFound,
    Exception\CannotGroupEmptyStructure,
};

/**
 * @template T
 * @template S
 */
final class ObjectKeys implements Implementation
{
    private string $keyType;
    private string $valueType;
    private ValidateArgument $validateKey;
    private ValidateArgument $validateValue;
    private \SplObjectStorage $values;

    public function __construct(string $keyType, string $valueType)
    {
        $this->validateKey = Type::of($keyType);

        if (!$this->validateKey instanceof ClassType && $keyType !== 'object') {
            throw new LogicException;
        }

        $this->validateValue = Type::of($valueType);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->values = new \SplObjectStorage;
    }

    public function keyType(): string
    {
        return $this->keyType;
    }

    public function valueType(): string
    {
        return $this->valueType;
    }

    public function size(): int
    {
        return $this->values->count();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->size();
    }

    /**
     * @param T $key
     * @param S $value
     *
     * @return self<T, S>
     */
    public function __invoke($key, $value): Implementation
    {
        ($this->validateKey)($key, 1);
        ($this->validateValue)($value, 2);

        $map = clone $this;
        $map->values = clone $this->values;
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $map->values[$key] = $value;

        return $map;
    }

    /**
     * @param T $key
     *
     * @throws ElementNotFound
     *
     * @return S
     */
    public function get($key)
    {
        if (!$this->contains($key)) {
            throw new ElementNotFound($key);
        }

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var S
         */
        return $this->values->offsetGet($key);
    }

    /**
     * @param T $key
     */
    public function contains($key): bool
    {
        if (!\is_object($key)) {
            return false;
        }

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return $this->values->offsetExists($key);
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        $map = clone $this;
        $map->values = new \SplObjectStorage;

        return $map;
    }

    /**
     * @param Implementation<T, S> $map
     */
    public function equals(Implementation $map): bool
    {
        if ($map->size() !== $this->size()) {
            return false;
        }

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            if (!$map->contains($key)) {
                return false;
            }

            if ($map->get($key) !== $v) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    public function filter(callable $predicate): self
    {
        $map = $this->clear();

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            if ($predicate($key, $v) === true) {
                $map->values[$k] = $v;
            }
        }

        return $map;
    }

    /**
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): void
    {
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            $function($key, $v);
        }
    }

    /**
     * @template D
     * @param callable(T, S): D $discriminator
     *
     * @throws CannotGroupEmptyStructure
     *
     * @return Map<D, Map<T, S>>
     */
    public function groupBy(callable $discriminator): Map
    {
        if ($this->empty()) {
            throw new CannotGroupEmptyStructure;
        }

        $groups = null;

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            $discriminant = $discriminator($key, $v);

            if ($groups === null) {
                /** @var Map<D, Map<T, S>> */
                $groups = Map::of(
                    Type::determine($discriminant),
                    Map::class,
                );
            }

            if ($groups->contains($discriminant)) {
                /** @var Map<T, S> */
                $group = $groups->get($discriminant);
                /** @var Map<T, S> */
                $group = ($group)($key, $v);

                $groups = ($groups)($discriminant, $group);
            } else {
                /** @var Map<T, S> */
                $group = $this->clearMap()($key, $v);

                $groups = ($groups)($discriminant, $group);
            }
        }

        /** @var Map<D, Map<T, S>> */
        return $groups;
    }

    /**
     * @return Set<T>
     */
    public function keys(): Set
    {
        return $this->reduce(
            Set::of($this->keyType),
            static fn(Set $keys, $key): Set => ($keys)($key),
        );
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this->reduce(
            Sequence::of($this->valueType),
            static fn(Sequence $values, $key, $value): Sequence => ($values)($value),
        );
    }

    /**
     * @param callable(T, S): (S|Pair<T, S>) $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self
    {
        $map = $this->clear();

        foreach ($this->values as $k) {
            /** @var T */
            $key = $k;
            /** @var S */
            $v = $this->values[$k];

            $return = $function($key, $v);

            if ($return instanceof Pair) {
                ($this->validateKey)($return->key(), 1);

                /** @var object */
                $key = $return->key();
                /** @var S */
                $value = $return->value();
            } else {
                $key = $k;
                $value = $return;
            }

            ($this->validateValue)($value, 2);

            $map->values[$key] = $value;
        }

        return $map;
    }

    /**
     * @param T $key
     *
     * @return self<T, S>
     */
    public function remove($key): self
    {
        if (!$this->contains($key)) {
            return $this;
        }

        $map = clone $this;
        $map->values = clone $this->values;
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $map->values->detach($key);
        $map->values->rewind();

        return $map;
    }

    /**
     * @param Implementation<T, S> $map
     *
     * @return self<T, S>
     */
    public function merge(Implementation $map): self
    {
        return $map->reduce(
            $this,
            static fn(self $carry, $key, $value): self => ($carry)($key, $value),
        );
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return Map<bool, Map<T, S>>
     */
    public function partition(callable $predicate): Map
    {
        $truthy = $this->clearMap();
        $falsy = $this->clearMap();

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            $return = $predicate($key, $v);

            if ($return === true) {
                $truthy = ($truthy)($key, $v);
            } else {
                $falsy = ($falsy)($key, $v);
            }
        }

        /**
         * @psalm-suppress InvalidScalarArgument
         * @psalm-suppress InvalidArgument
         * @var Map<bool, Map<T, S>>
         */
        return Map::of('bool', Map::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * @template R
     * @param R $carry
     * @param callable(R, T, S): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            $carry = $reducer($carry, $key, $v);
        }

        return $carry;
    }

    public function empty(): bool
    {
        $this->values->rewind();

        return !$this->values->valid();
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
        /** @var Sequence<ST> */
        $sequence = Sequence::of($type);

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            foreach ($mapper($key, $v) as $newValue) {
                $sequence = ($sequence)($newValue);
            }
        }

        return $sequence;
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
        /** @var Set<ST> */
        $set = Set::of($type);

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            foreach ($mapper($key, $v) as $newValue) {
                $set = ($set)($newValue);
            }
        }

        return $set;
    }

    /**
     * @template MT
     * @template MS
     *
     * @param null|callable(T, S): \Generator<MT, MS> $mapper
     *
     * @return Map<MT, MS>
     */
    public function toMapOf(string $key, string $value, callable $mapper = null): Map
    {
        /** @psalm-suppress MissingParamType */
        $mapper ??= static fn($k, $v): \Generator => yield $k => $v;

        /** @var Map<MT, MS> */
        $map = Map::of($key, $value);

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            foreach ($mapper($key, $v) as $newKey => $newValue) {
                $map = ($map)($newKey, $newValue);
            }
        }

        return $map;
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of($this->keyType, $this->valueType);
    }
}
