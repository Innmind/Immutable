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
final class Uninitialized implements Implementation
{
    /**
     * @param T $key
     * @param S $value
     *
     * @return Implementation<T, S>
     */
    #[\Override]
    public function __invoke($key, $value): Implementation
    {
        return self::open($key, $value);
    }

    /**
     * @template A
     * @template B
     * @psalm-pure
     *
     * @param A $key
     * @param B $value
     *
     * @return Implementation<A, B>
     */
    public static function open($key, $value): Implementation
    {
        return ObjectKeys::of($key, $value)
            ->otherwise(static fn() => Primitive::of($key, $value))
            ->match(
                static fn($implementation) => $implementation,
                static fn() => DoubleIndex::of($key, $value),
            );
    }

    #[\Override]
    public function size(): int
    {
        return 0;
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
        return Maybe::nothing();
    }

    /**
     * @param T $key
     */
    #[\Override]
    public function contains($key): bool
    {
        return false;
    }

    /**
     * @return self<T, S>
     */
    #[\Override]
    public function clear(): self
    {
        return $this;
    }

    /**
     * @param Implementation<T, S> $map
     */
    #[\Override]
    public function equals(Implementation $map): bool
    {
        return $map->empty();
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    #[\Override]
    public function filter(callable $predicate): self
    {
        return $this;
    }

    /**
     * @param callable(T, S): void $function
     */
    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        return SideEffect::identity();
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
        return Map::of();
    }

    /**
     * @return Set<T>
     */
    #[\Override]
    public function keys(): Set
    {
        /** @var Set<T> */
        return Set::of();
    }

    /**
     * @return Sequence<S>
     */
    #[\Override]
    public function values(): Sequence
    {
        /** @var Sequence<S> */
        return Sequence::of();
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
        return new self;
    }

    /**
     * @param T $key
     *
     * @return self<T, S>
     */
    #[\Override]
    public function remove($key): self
    {
        return $this;
    }

    /**
     * @param Implementation<T, S> $map
     *
     * @return Implementation<T, S>
     */
    #[\Override]
    public function merge(Implementation $map): Implementation
    {
        return $map;
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return Map<bool, Map<T, S>>
     */
    #[\Override]
    public function partition(callable $predicate): Map
    {
        return Map::of(
            [true, $this->clearMap()],
            [false, $this->clearMap()],
        );
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
        return $carry;
    }

    #[\Override]
    public function empty(): bool
    {
        return true;
    }

    #[\Override]
    public function find(callable $predicate): Maybe
    {
        /** @var Maybe<Pair<T, S>> */
        return Maybe::nothing();
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        return Sequence::of();
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of();
    }
}
