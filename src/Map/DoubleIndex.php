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
 * @psalm-immutable
 */
final class DoubleIndex implements Implementation
{
    /** @var Sequence\Implementation<Pair<T, S>> */
    private Sequence\Implementation $pairs;

    /**
     * @param Sequence\Implementation<Pair<T, S>> $pairs
     */
    public function __construct(Sequence\Implementation $pairs = null)
    {
        /** @var Sequence\Implementation<Pair<T, S>> */
        $this->pairs = $pairs ?? new Sequence\Primitive;
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

        return new self(($map->pairs)(new Pair($key, $value)));
    }

    /**
     * @template A
     * @template B
     * @psalm-pure
     *
     * @param A $key
     * @param B $value
     *
     * @return self<A, B>
     */
    public static function of($key, $value): self
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return new self(new Sequence\Primitive([new Pair($key, $value)]));
    }

    public function size(): int
    {
        return $this->pairs->size();
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
        return new self($this->pairs->clear());
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
        return new self($this->pairs->filter(
            static fn($pair) => $predicate($pair->key(), $pair->value()),
        ));
    }

    /**
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        foreach ($this->pairs->iterator() as $pair) {
            $function($pair->key(), $pair->value());
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
        return new self($this->pairs->map(static fn($pair) => new Pair(
            $pair->key(),
            $function($pair->key(), $pair->value()),
        )));
    }

    /**
     * @param T $key
     *
     * @return self<T, S>
     */
    public function remove($key): self
    {
        return new self($this->pairs->filter(
            static fn($pair) => $pair->key() !== $key,
        ));
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

    public function find(callable $predicate): Maybe
    {
        return $this->pairs->find(static fn($pair) => $predicate($pair->key(), $pair->value()));
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of();
    }
}
