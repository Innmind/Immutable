<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
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
final class DoubleIndex implements Implementation
{
    /** @var Sequence\Implementation<Pair<T, S>> */
    private Sequence\Implementation $pairs;

    /**
     * @param Sequence\Implementation<Pair<T, S>> $pairs
     */
    public function __construct(?Sequence\Implementation $pairs = null)
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
    #[\Override]
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

    #[\Override]
    public function size(): int
    {
        return $this->pairs->size();
    }

    #[\Override]
    public function count(): int
    {
        return $this->size();
    }

    /**
     * @param T $key
     *
     * @return Maybe<S>
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function clear(): self
    {
        return new self($this->pairs->clear());
    }

    /**
     * @param Implementation<T, S> $map
     */
    #[\Override]
    public function equals(Implementation $map): bool
    {
        if (!$map->keys()->equals($this->keys())) {
            return false;
        }

        return $this
            ->pairs
            ->find(
                static fn($pair) => $map
                    ->get($pair->key())
                    ->filter(static fn($value) => $value === $pair->value())
                    ->match(
                        static fn() => false,
                        static fn() => true,
                    ),
            )
            ->match(
                static fn() => false,
                static fn() => true,
            );
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    #[\Override]
    public function filter(callable $predicate): self
    {
        return new self($this->pairs->filter(
            static fn($pair) => $predicate($pair->key(), $pair->value()),
        ));
    }

    /**
     * @param callable(T, S): void $function
     */
    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $this->pairs->foreach(static fn($pair) => $function(
            $pair->key(),
            $pair->value(),
        ));
    }

    /**
     * @template D
     *
     * @param callable(T, S): D $discriminator
     *
     * @return Map<D, Map<T, S>>
     */
    #[\Override]
    public function groupBy(callable $discriminator): Map
    {
        /** @var Map<D, Map<T, S>> */
        $groups = Map::of();
        $empty = $this->clearMap();

        /** @var Map<D, Map<T, S>> */
        return $this->pairs->reduce(
            $groups,
            static function(Map $groups, $pair) use ($discriminator, $empty) {
                /** @psalm-suppress ImpureFunctionCall */
                $key = $discriminator($pair->key(), $pair->value());
                /** @var Map<T, S> */
                $group = $groups->get($key)->match(
                    static fn(Map $group) => $group,
                    static fn() => $empty,
                );

                return ($groups)(
                    $key,
                    ($group)(
                        $pair->key(),
                        $pair->value(),
                    ),
                );
            }
        );
    }

    /**
     * @return Set<T>
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function partition(callable $predicate): Map
    {
        $truthy = $this->clearMap();
        $falsy = $this->clearMap();

        [$truthy, $falsy] = $this->pairs->reduce(
            [$truthy, $falsy],
            static function(array $carry, $pair) use ($predicate) {
                /**
                 * @var Map<T, S> $truthy
                 * @var Map<T, S> $falsy
                 */
                [$truthy, $falsy] = $carry;

                $key = $pair->key();
                $value = $pair->value();

                /** @psalm-suppress ImpureFunctionCall */
                $return = $predicate($key, $value);

                if ($return === true) {
                    $truthy = ($truthy)($key, $value);
                } else {
                    $falsy = ($falsy)($key, $value);
                }

                return [$truthy, $falsy];
            },
        );

        return Map::of([true, $truthy], [false, $falsy]);
    }

    /**
     * @template I
     * @template R
     *
     * @param I $carry
     * @param callable(I|R, T, S): R $reducer
     *
     * @return I|R
     */
    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        /**
         * @psalm-suppress ImpureFunctionCall
         * @psalm-suppress MixedArgument
         */
        return $this->pairs->reduce(
            $carry,
            static fn($carry, $pair) => $reducer(
                $carry,
                $pair->key(),
                $pair->value(),
            ),
        );
    }

    #[\Override]
    public function empty(): bool
    {
        return $this->pairs->empty();
    }

    #[\Override]
    public function find(callable $predicate): Maybe
    {
        return $this->pairs->find(static fn($pair) => $predicate($pair->key(), $pair->value()));
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        return $this->pairs->toSequence();
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of();
    }
}
