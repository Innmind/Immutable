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
    Exception\ElementNotFound,
};

/**
 * @template T
 * @template S
 */
final class DoubleIndex implements Implementation
{
    /** @var Sequence\Implementation<T> */
    private Sequence\Implementation $keys;
    /** @var Sequence\Implementation<S> */
    private Sequence\Implementation $values;
    /** @var Sequence\Implementation<Pair<T, S>> */
    private Sequence\Implementation $pairs;

    public function __construct()
    {
        $this->keys = new Sequence\Primitive;
        $this->values = new Sequence\Primitive;
        /** @var Sequence\Implementation<Pair<T, S>> */
        $this->pairs = new Sequence\Primitive;
    }

    /**
     * @param T $key
     * @param S $value
     *
     * @return self<T, S>
     */
    public function __invoke($key, $value): self
    {
        $map = clone $this;

        if ($this->keys->contains($key)) {
            $index = $this->keys->indexOf($key);
            $map->values = $this->values->take($index)($value)->append($this->values->drop($index + 1));
            $map->pairs = $this->pairs->take($index)(new Pair($key, $value))->append($this->pairs->drop($index + 1));
        } else {
            $map->keys = ($this->keys)($key);
            $map->values = ($this->values)($value);
            $map->pairs = ($this->pairs)(new Pair($key, $value));
        }

        return $map;
    }

    /**
     * @template A
     * @template B
     *
     * @param A $key
     * @param B $value
     *
     * @return self<A, B>
     */
    public static function of($key, $value): self
    {
        /** @var self<A, B> */
        $self = new self;

        return ($self)($key, $value);
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
                $map->keys = ($map->keys)($pair->key());
                $map->values = ($map->values)($pair->value());
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
     *
     * @param callable(T, S): D $discriminator
     *
     * @return Map<D, Map<T, S>>
     */
    public function groupBy(callable $discriminator): Map
    {
        /** @var Map<D, Map<T, S>> */
        $groups = Map::of();

        foreach ($this->pairs->iterator() as $pair) {
            $key = $discriminator($pair->key(), $pair->value());

            if ($groups->contains($key)) {
                $group = $groups->get($key);
                $group = ($group)($pair->key(), $pair->value());

                $groups = ($groups)($key, $group);
            } else {
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
        return $this->keys->toSet();
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this->values->toSequence();
    }

    /**
     * @template B
     *
     * @param callable(T, S): B $function
     *
     * @return self<T, B>
     */
    public function map(callable $function): self
    {
        /** @var self<T, B> */
        $map = new self;

        foreach ($this->pairs->iterator() as $pair) {
            /** @psalm-suppress InvalidArgument */
            $map = ($map)(
                $pair->key(),
                $function($pair->key(), $pair->value()),
            );
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
     * @param Implementation<T, S> $map
     *
     * @return self<T, S>
     */
    public function merge(Implementation $map): self
    {
        /** @psalm-suppress MixedArgument For some reason it no longer recognize templates for $key and $value */
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
        return Map::of()
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
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of();
    }
}
