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
 * @template T
 * @template S
 */
final class DoubleIndex implements Implementation
{
    private string $keyType;
    private string $valueType;
    private ValidateArgument $validateKey;
    private ValidateArgument $validateValue;
    /** @var Sequence\Implementation<T> */
    private Sequence\Implementation $keys;
    /** @var Sequence\Implementation<S> */
    private Sequence\Implementation $values;
    /** @var Sequence\Implementation<Pair<T, S>> */
    private Sequence\Implementation $pairs;

    public function __construct(string $keyType, string $valueType)
    {
        $this->validateKey = Type::of($keyType);
        $this->validateValue = Type::of($valueType);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->keys = new Sequence\Primitive($keyType);
        $this->values = new Sequence\Primitive($valueType);
        /** @var Sequence\Implementation<Pair<T, S>> */
        $this->pairs = new Sequence\Primitive(Pair::class);
    }

    /**
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

        if ($this->keys->contains($key)) {
            $index = $this->keys->indexOf($key);
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $map->values = $this->values->take($index)($value)->append($this->values->drop($index + 1));
            /**
             * @psalm-suppress MixedArgumentTypeCoercion
             * @var Sequence\Implementation<Pair<T, S>>
             */
            $map->pairs = $this->pairs->take($index)(new Pair($key, $value))->append($this->pairs->drop($index + 1));
        } else {
            /** @var Sequence\Implementation<T> */
            $map->keys = ($this->keys)($key);
            $map->values = ($this->values)($value);
            /** @var Sequence\Implementation<Pair<T, S>> */
            $map->pairs = ($this->pairs)(new Pair($key, $value));
        }

        return $map;
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
        return $this->keys->size();
    }

    public function count(): int
    {
        return $this->keys->count();
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
        if (!$this->keys->contains($key)) {
            throw new ElementNotFound($key);
        }

        return $this->values->get(
            $this->keys->indexOf($key),
        );
    }

    /**
     * @param T $key
     */
    public function contains($key): bool
    {
        return $this->keys->contains($key);
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        $map = clone $this;
        $map->keys = $this->keys->clear();
        $map->values = $this->values->clear();
        $map->pairs = $this->pairs->clear();

        return $map;
    }

    /**
     * @param Implementation<T, S> $map
     */
    public function equals(Implementation $map): bool
    {
        if (!$map->keys()->equals($this->keys())) {
            return false;
        }

        foreach ($this->pairs->iterator() as $pair) {
            if ($map->get($pair->key()) !== $pair->value()) {
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

        foreach ($this->pairs->iterator() as $pair) {
            if ($predicate($pair->key(), $pair->value()) === true) {
                /** @var Sequence\Implementation<T> */
                $map->keys = ($map->keys)($pair->key());
                /** @var Sequence\Implementation<S> */
                $map->values = ($map->values)($pair->value());
                /** @var Sequence\Implementation<Pair<T, S>> */
                $map->pairs = ($map->pairs)($pair);
            }
        }

        return $map;
    }

    /**
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): void
    {
        foreach ($this->pairs->iterator() as $pair) {
            $function($pair->key(), $pair->value());
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

        foreach ($this->pairs->iterator() as $pair) {
            $key = $discriminator($pair->key(), $pair->value());

            if ($groups === null) {
                /** @var Map<D, Map<T, S>> */
                $groups = Map::of(
                    Type::determine($key),
                    Map::class,
                );
            }

            if ($groups->contains($key)) {
                /** @var Map<T, S> */
                $group = $groups->get($key);
                /** @var Map<T, S> */
                $group = ($group)($pair->key(), $pair->value());

                $groups = ($groups)($key, $group);
            } else {
                /** @var Map<T, S> */
                $group = $this->clearMap()($pair->key(), $pair->value());

                $groups = ($groups)($key, $group);
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
        return $this->keys->toSetOf($this->keyType);
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this->values->toSequenceOf($this->valueType);
    }

    /**
     * @param callable(T, S): (S|Pair<T, S>) $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self
    {
        $map = $this->clear();

        foreach ($this->pairs->iterator() as $pair) {
            $return = $function($pair->key(), $pair->value());

            if ($return instanceof Pair) {
                /** @var T */
                $key = $return->key();
                /** @var S */
                $value = $return->value();
            } else {
                $key = $pair->key();
                $value = $return;
            }

            $map = ($map)($key, $value);
        }

        return $map;
    }

    /**
     * @param T $key
     *
     * @return self<T, S>
     */
    public function remove($key): Implementation
    {
        if (!$this->contains($key)) {
            return $this;
        }

        $index = $this->keys->indexOf($key);
        $map = clone $this;
        /** @var Sequence\Implementation<T> */
        $map->keys = $this
            ->keys
            ->slice(0, $index)
            ->append($this->keys->slice($index + 1, $this->keys->size()));
        $map->values = $this
            ->values
            ->slice(0, $index)
            ->append($this->values->slice($index + 1, $this->values->size()));
        /** @var Sequence\Implementation<Pair<T, S>> */
        $map->pairs = $this
            ->pairs
            ->slice(0, $index)
            ->append($this->pairs->slice($index + 1, $this->pairs->size()));

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

        foreach ($this->pairs->iterator() as $pair) {
            $key = $pair->key();
            $value = $pair->value();

            $return = $predicate($key, $value);

            if ($return === true) {
                $truthy = ($truthy)($key, $value);
            } else {
                $falsy = ($falsy)($key, $value);
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
        foreach ($this->pairs->iterator() as $pair) {
            $carry = $reducer($carry, $pair->key(), $pair->value());
        }

        return $carry;
    }

    public function empty(): bool
    {
        return $this->pairs->empty();
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

        foreach ($this->pairs->iterator() as $pair) {
            foreach ($mapper($pair->key(), $pair->value()) as $newValue) {
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

        foreach ($this->pairs->iterator() as $pair) {
            foreach ($mapper($pair->key(), $pair->value()) as $newValue) {
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
        /** @psalm-suppress MissingClosureParamType */
        $mapper ??= static fn($k, $v): \Generator => yield $k => $v;

        /** @var Map<MT, MS> */
        $map = Map::of($key, $value);

        foreach ($this->pairs->iterator() as $pair) {
            /**
             * @var MT $newKey
             * @var MS $newValue
             */
            foreach ($mapper($pair->key(), $pair->value()) as $newKey => $newValue) {
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
