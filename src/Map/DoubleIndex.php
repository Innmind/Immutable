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
    Maybe,
};

/**
 * @template T
 * @template S
 */
final class DoubleIndex implements Implementation
{
    /** @var Sequence\Implementation<Pair<T, S>> */
    private Sequence\Implementation $pairs;

    public function __construct()
    {
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
        $map = $this->remove($key);
        $map->pairs = ($map->pairs)(new Pair($key, $value));

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
        return $this->pairs->size();
    }

    public function count(): int
    {
        return $this->pairs->count();
    }

    /**
     * @param T $key
     *
     * @return Maybe<S>
     */
    public function get($key): Maybe
    {
        return $this
            ->pairs
            ->find(static fn($pair) => $pair->key() === $key)
            ->map(static fn($pair) => $pair->value());
    }

    /**
     * @param T $key
     */
    public function contains($key): bool
    {
        return $this
            ->pairs
            ->find(static fn($pair) => $pair->key() === $key)
            ->match(
                static fn() => true,
                static fn() => false,
            );
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        $map = clone $this;
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
            $equals = $map
                ->get($pair->key())
                ->filter(static fn($value) => $value === $pair->value())
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
        $map->pairs = $this
            ->pairs
            ->filter(static fn($pair) => $predicate($pair->key(), $pair->value()));

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

            $group = $groups->get($key)->match(
                static fn($group) => $group,
                fn() => $this->clearMap(),
            );
            $groups = ($groups)($key, ($group)($pair->key(), $pair->value()));
        }

        /** @var Map<D, Map<T, S>> */
        return $groups;
    }

    /**
     * @return Set<T>
     */
    public function keys(): Set
    {
        return $this
            ->pairs
            ->map(static fn($pair) => $pair->key())
            ->toSet();
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        return $this
            ->pairs
            ->map(static fn($pair) => $pair->value())
            ->toSequence();
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
        $map = clone $this;
        $map->pairs = $this
            ->pairs
            ->filter(static fn($pair) => $pair->key() !== $key);

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
