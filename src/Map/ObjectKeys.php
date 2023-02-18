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
    SideEffect,
};

/**
 * @template T
 * @template S
 * @implements Implementation<T, S>
 * @psalm-immutable
 */
final class ObjectKeys implements Implementation
{
    private \SplObjectStorage $values;

    public function __construct(\SplObjectStorage $values = null)
    {
        $this->values = $values ?? new \SplObjectStorage;
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

        /** @var \SplObjectStorage<object, mixed> */
        $values = clone $this->values;
        /** @psalm-suppress ImpureMethodCall */
        $values[$key] = $value;

        return new self($values);
    }

    /**
     * @template A
     * @template B
     * @psalm-pure
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
        /**
         * @psalm-suppress ImpureMethodCall
         * @var 0|positive-int
         */
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
         * @psalm-suppress ImpureMethodCall
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

        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @psalm-suppress ImpureMethodCall
         */
        return $this->values->offsetExists($key);
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        return new self;
    }

    /**
     * @param Implementation<T, S> $map
     */
    public function equals(Implementation $map): bool
    {
        if (!$map->keys()->equals($this->keys())) {
            return false;
        }

        /** @psalm-suppress ImpureMethodCall */
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
        /** @var \SplObjectStorage<object, mixed> */
        $values = new \SplObjectStorage;

        /** @psalm-suppress ImpureMethodCall */
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /** @var S $v */
            $v = $this->values[$k];

            /** @psalm-suppress ImpureFunctionCall */
            if ($predicate($key, $v) === true) {
                $values[$k] = $v;
            }
        }

        return new self($values);
    }

    /**
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        /** @psalm-suppress ImpureMethodCall */
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /**
             * @psalm-suppress ImpureMethodCall
             * @var S $v
             */
            $v = $this->values[$k];

            /** @psalm-suppress ImpureFunctionCall */
            $function($key, $v);
        }

        return new SideEffect;
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

        /** @psalm-suppress ImpureMethodCall */
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /**
             * @psalm-suppress ImpureMethodCall
             * @var S
             */
            $v = $this->values[$k];

            /** @psalm-suppress ImpureFunctionCall */
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
        /** @var \SplObjectStorage<object, mixed> */
        $values = new \SplObjectStorage;

        /** @psalm-suppress ImpureMethodCall */
        foreach ($this->values as $k) {
            /** @var T */
            $key = $k;
            /**
             * @psalm-suppress ImpureMethodCall
             * @var S
             */
            $v = $this->values[$k];

            /** @psalm-suppress ImpureFunctionCall */
            $values[$k] = $function($key, $v);
        }

        return new self($values);
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

        /** @var \SplObjectStorage<object, mixed> */
        $values = clone $this->values;
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @psalm-suppress ImpureMethodCall
         */
        $values->detach($key);
        /** @psalm-suppress ImpureMethodCall */
        $values->rewind();

        return new self($values);
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

        /** @psalm-suppress ImpureMethodCall */
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /**
             * @psalm-suppress ImpureMethodCall
             * @var S $v
             */
            $v = $this->values[$k];

            /** @psalm-suppress ImpureFunctionCall */
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
        /** @psalm-suppress ImpureMethodCall */
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /**
             * @psalm-suppress ImpureMethodCall
             * @var S $v
             */
            $v = $this->values[$k];

            /** @psalm-suppress ImpureFunctionCall */
            $carry = $reducer($carry, $key, $v);
        }

        return $carry;
    }

    public function empty(): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        $this->values->rewind();

        /** @psalm-suppress ImpureMethodCall */
        return !$this->values->valid();
    }

    public function find(callable $predicate): Maybe
    {
        /** @psalm-suppress ImpureMethodCall */
        foreach ($this->values as $k) {
            /** @var T $key */
            $key = $k;
            /**
             * @psalm-suppress ImpureMethodCall
             * @var S $v
             */
            $v = $this->values[$k];

            /** @psalm-suppress ImpureFunctionCall */
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
