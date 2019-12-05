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
use function Innmind\Immutable\unwrap;

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
    /** @var Sequence<T> */
    private Sequence $keys;
    /** @var Sequence<S> */
    private Sequence $values;
    /** @var Sequence<Pair<T, S>> */
    private Sequence $pairs;

    public function __construct(string $keyType, string $valueType)
    {
        $this->validateKey = Type::of($keyType);
        $this->validateValue = Type::of($valueType);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->keys = Sequence::of($keyType);
        $this->values = Sequence::of($valueType);
        /** @var Sequence<Pair<T, S>> */
        $this->pairs = Sequence::of(Pair::class);
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
     * @param S $value
     *
     * @return self<T, S>
     */
    public function put($key, $value): self
    {
        ($this->validateKey)($key, 1);
        ($this->validateValue)($value, 2);

        $map = clone $this;

        if ($this->keys->contains($key)) {
            $index = $this->keys->indexOf($key);
            $map->values = $this->values->take($index)
                ->add($value)
                ->append($this->values->drop($index + 1));
            /** @var Sequence<Pair<T, S>> */
            $map->pairs = $this->pairs->take($index)
                ->add(new Pair($key, $value))
                ->append($this->pairs->drop($index + 1));
        } else {
            /** @var Sequence<T> */
            $map->keys = ($this->keys)($key);
            $map->values = ($this->values)($value);
            /** @var Sequence<Pair<T, S>> */
            $map->pairs = ($this->pairs)(new Pair($key, $value));
        }

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
        if (!$this->keys->contains($key)) {
            throw new ElementNotFound($key);
        }

        return $this->values->get(
            $this->keys->indexOf($key)
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

        foreach ($this->pairs->toArray() as $pair) {
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

        foreach ($this->pairs->toArray() as $pair) {
            if ($predicate($pair->key(), $pair->value()) === true) {
                /** @psalm-suppress MixedArgumentTypeCoercion */
                $map->keys = ($map->keys)($pair->key());
                /** @psalm-suppress MixedArgumentTypeCoercion */
                $map->values = ($map->values)($pair->value());
                /**
                 * @psalm-suppress MixedArgumentTypeCoercion
                 * @var Sequence<Pair<T, S>>
                 */
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
        foreach ($this->pairs->toArray() as $pair) {
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

        foreach ($this->pairs->toArray() as $pair) {
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
        return Set::of($this->keyType, ...$this->keys->toArray());
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this->values;
    }

    /**
     * @param callable(T, S): (S|Pair<T, S>) $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self
    {
        $map = $this->clear();

        foreach ($this->pairs->toArray() as $pair) {
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

            $map = $map->put($key, $value);
        }

        return $map;
    }

    public function join(string $separator): Str
    {
        return $this->values->join($separator);
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
        $map->keys = $this
            ->keys
            ->slice(0, $index)
            ->append($this->keys->slice($index + 1, $this->keys->size()));
        $map->values = $this
            ->values
            ->slice(0, $index)
            ->append($this->values->slice($index + 1, $this->values->size()));
        /** @var Sequence<Pair<T, S>> */
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
            function(self $carry, $key, $value): self {
                return $carry->put($key, $value);
            }
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

        foreach ($this->pairs->toArray() as $pair) {
            $return = $predicate($pair->key(), $pair->value());

            if ($return === true) {
                $truthy = ($truthy)($pair->key(), $pair->value());
            } else {
                $falsy = ($falsy)($pair->key(), $pair->value());
            }
        }

        /**
         * @psalm-suppress InvalidScalarArgument
         * @psalm-suppress InvalidArgument
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

        foreach (unwrap($this->pairs) as $pair) {
            foreach ($mapper($pair->key(), $pair->value()) as $newValue) {
                $set = ($set)($newValue);
            }
        }

        return $set;
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of($this->keyType, $this->valueType);
    }
}
