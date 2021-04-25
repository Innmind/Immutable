<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Pair,
    Exception\ElementNotFound,
};

/**
 * @template T
 * @template S
 */
final class Uninitialized implements Implementation
{
    /**
     * @param T $key
     * @param S $value
     *
     * @return Implementation<T, S>
     */
    public function __invoke($key, $value): Implementation
    {
        return self::open($key, $value);
    }

    /**
     * @template A
     * @template B
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

    public function size(): int
    {
        return 0;
    }

    public function count(): int
    {
        return $this->size();
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
        throw new ElementNotFound($key);
    }

    /**
     * @param T $key
     */
    public function contains($key): bool
    {
        return false;
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        return $this;
    }

    /**
     * @param Implementation<T, S> $map
     */
    public function equals(Implementation $map): bool
    {
        return $map->empty();
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return self<T, S>
     */
    public function filter(callable $predicate): self
    {
        return $this;
    }

    /**
     * @param callable(T, S): void $function
     */
    public function foreach(callable $function): void
    {
        // noop
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
        return Map::of();
    }

    /**
     * @return Set<T>
     */
    public function keys(): Set
    {
        /** @var Set<T> */
        return Set::of();
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        /** @var Sequence<S> */
        return Sequence::of();
    }

    /**
     * @param callable(T, S): S $function
     *
     * @return self<T, S>
     */
    public function map(callable $function): self
    {
        return $this;
    }

    /**
     * @param T $key
     *
     * @return self<T, S>
     */
    public function remove($key): self
    {
        return $this;
    }

    /**
     * @param Implementation<T, S> $map
     *
     * @return Implementation<T, S>
     */
    public function merge(Implementation $map): Implementation
    {
        return $map;
    }

    /**
     * @param callable(T, S): bool $predicate
     *
     * @return Map<bool, Map<T, S>>
     */
    public function partition(callable $predicate): Map
    {
        /**
         * @psalm-suppress InvalidScalarArgument
         * @psalm-suppress InvalidArgument
         * @var Map<bool, Map<T, S>>
         */
        return Map::of()
            (true, $this->clearMap())
            (false, $this->clearMap());
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
        return $carry;
    }

    public function empty(): bool
    {
        return true;
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
        return Sequence::of();
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
        return Set::of();
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
        /** @var Map<MT, MS> */
        return Map::of();
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of();
    }
}
