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
    Exception\ElementNotFound,
};

/**
 * @template T
 * @template S
 */
final class Primitive implements Implementation
{
    /** @var array<T, S> */
    private array $values = [];
    private ?int $size = null;

    public function __construct()
    {
    }

    /**
     * @param T $key
     * @param S $value
     *
     * @return Implementation<T, S>
     */
    public function __invoke($key, $value): Implementation
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (\is_string($key) && \is_numeric($key)) {
            // numeric-string keys are casted to ints by php, so when iterating
            // over the array afterward the type is not conserved so we switch
            // the implementation to DoubleIndex so keep the type
            return (new DoubleIndex)->merge($this)($key, $value);
        }

        $map = clone $this;
        $map->size = null;
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
        /** @psalm-suppress DocblockTypeContradiction */
        if (\is_string($key) && \is_numeric($key)) {
            /** @var Maybe<Implementation<A, B>> */
            return Maybe::nothing();
        }

        if (\is_string($key) || \is_int($key)) {
            /** @var self<A, B> */
            $self = new self;

            return Maybe::just(($self)($key, $value));
        }

        /** @var Maybe<Implementation<A, B>> */
        return Maybe::nothing();
    }

    public function size(): int
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return $this->size ?? $this->size = \count($this->values);
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
        if (!$this->contains($key)) {
            throw new ElementNotFound($key);
        }

        return $this->values[$key];
    }

    /**
     * @param T $key
     */
    public function contains($key): bool
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return \array_key_exists($key, $this->values);
    }

    /**
     * @return self<T, S>
     */
    public function clear(): self
    {
        $map = clone $this;
        $map->size = null;
        $map->values = [];

        return $map;
    }

    /**
     * @param Implementation<T, S> $map
     */
    public function equals(Implementation $map): bool
    {
        if ($map->size() !== $this->size()) {
            return false;
        }

        foreach ($this->values as $k => $v) {
            if (!$map->contains($k)) {
                return false;
            }

            if ($map->get($k) !== $v) {
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

        foreach ($this->values as $k => $v) {
            if ($predicate($k, $v) === true) {
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
        foreach ($this->values as $k => $v) {
            $function($k, $v);
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

        foreach ($this->values as $key => $value) {
            $discriminant = $discriminator($key, $value);

            if ($groups->contains($discriminant)) {
                $group = $groups->get($discriminant);
                $group = ($group)($key, $value);

                $groups = ($groups)($discriminant, $group);
            } else {
                $group = $this->clearMap()($key, $value);

                $groups = ($groups)($discriminant, $group);
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
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $keys = \array_keys($this->values);

        return Set::of(...$keys);
    }

    /**
     * @return Sequence<S>
     */
    public function values(): Sequence
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        $values = \array_values($this->values);

        return Sequence::of(...$values);
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

        foreach ($this->values as $k => $v) {
            $map->values[$k] = $function($k, $v);
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
        $map->size = null;
        /** @psalm-suppress MixedArrayTypeCoercion */
        unset($map->values[$key]);

        return $map;
    }

    /**
     * @param Implementation<T, S> $map
     *
     * @return Implementation<T, S>
     */
    public function merge(Implementation $map): Implementation
    {
        /** @psalm-suppress MixedArgument For some reason it no longer recognize templates for $key and $value */
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

        foreach ($this->values as $k => $v) {
            $return = $predicate($k, $v);

            if ($return === true) {
                $truthy = ($truthy)($k, $v);
            } else {
                $falsy = ($falsy)($k, $v);
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
        foreach ($this->values as $k => $v) {
            $carry = $reducer($carry, $k, $v);
        }

        return $carry;
    }

    public function empty(): bool
    {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        \reset($this->values);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        return \is_null(\key($this->values));
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
        $sequence = Sequence::of();

        foreach ($this->values as $key => $value) {
            foreach ($mapper($key, $value) as $newValue) {
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
        $set = Set::of();

        foreach ($this->values as $key => $value) {
            foreach ($mapper($key, $value) as $newValue) {
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
        $map = Map::of();

        foreach ($this->values as $key => $value) {
            /**
             * @var MT $newKey
             * @var MS $newValue
             */
            foreach ($mapper($key, $value) as $newKey => $newValue) {
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
        return Map::of();
    }
}
