<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
    Str,
    Sequence,
    Set,
    Pair,
    Maybe,
};

/**
 * @template T
 * @template S
 */
final class ObjectKeys implements Implementation
{
    private \SplObjectStorage $values;

    public function __construct()
    {
        $this->values = new \SplObjectStorage;
    }

    /**
     * @param T $key
     * @param S $value
     *
     * @return Implementation<T, S>
     */
    public function __invoke($key, $value): Implementation
    {
        if (!\is_object($key)) {
            return (new DoubleIndex)->merge($this)($key, $value);
        }

        $map = clone $this;
        $map->values = clone $this->values;
        $map->values[$key] = $value;

        return $map;
    }

    /**
     * @template A
     * @template B
     *
     * @param A $key
     * @param B $value
     *
     * @return Maybe<Implementation<A, B>>
     */
    public static function of($key, $value): Maybe
    {
        if (\is_object($key)) {
            /** @var self<A, B> */
            $self = new self;

            /** @var Maybe<Implementation<A, B>> */
            return Maybe::just(($self)($key, $value));
        }

        /** @var Maybe<Implementation<A, B>> */
        return Maybe::nothing();
    }

    public function size(): int
    {
        return $this->values->count();
    }

    public function count(): int
    {
        return $this->size();
    }

    /**
     * @param T $key
     *
     * @return Maybe<S>
     */
    public function get($key): Maybe
    {
        if (!$this->contains($key)) {
            /** @var Maybe<S> */
            return Maybe::nothing();
        }

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Maybe<S>
         */
        return Maybe::just($this->values->offsetGet($key));
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
        if (!$map->keys()->equals($this->keys())) {
            return false;
        }

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];
            $equals = $map
                ->get($key)
                ->filter(static fn($value) => $value === $v)
                ->match(
                    static fn() => true,
                    static fn() => false,
                );

            if (!$equals) {
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
     *
     * @param callable(T, S): D $discriminator
     *
     * @return Map<D, Map<T, S>>
     */
    public function groupBy(callable $discriminator): Map
    {
        /** @var Map<D, Map<T, S>> */
        $groups = Map::of();

        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            $discriminant = $discriminator($key, $v);

            $group = $groups->get($discriminant)->match(
                static fn($group) => $group,
                fn() => $this->clearMap(),
            );
            $groups = ($groups)($discriminant, ($group)($key, $v));
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
            Set::of(),
            static fn(Set $keys, $key): Set => ($keys)($key),
        );
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this->reduce(
            Sequence::of(),
            static fn(Sequence $values, $_, $value): Sequence => ($values)($value),
        );
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
        $map = new self;

        foreach ($this->values as $k) {
            /** @var T */
            $key = $k;
            /** @var S */
            $v = $this->values[$k];

            $map->values[$k] = $function($key, $v);
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
     * @return Implementation<T, S>
     */
    public function merge(Implementation $map): Implementation
    {
        return $map->reduce(
            $this,
            static fn(Implementation $carry, $key, $value): Implementation => ($carry)($key, $value),
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

        return Map::of([true, $truthy], [false, $falsy]);
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

    public function find(callable $predicate): Maybe
    {
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            if ($predicate($key, $v)) {
                return Maybe::just(new Pair($key, $v));
            }
        }

        /** @var Maybe<Pair<T, S>> */
        return Maybe::nothing();
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of();
    }
}
