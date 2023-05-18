<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Str,
    Maybe,
    SideEffect,
};

/**
 * @template T
 * @implements Implementation<T>
 * @psalm-immutable
 * @psalm-type RegisterCleanup = callable(callable(): void): void
 */
final class Lazy implements Implementation
{
    /** @var Sequence\Implementation<T> */
    private Sequence\Implementation $values;

    /**
     * @param Sequence\Implementation<T> $values
     */
    public function __construct(Sequence\Implementation $values)
    {
        $this->values = $values;
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        return self::distinct(($this->values)($element));
    }

    /**
     * @template A
     * @psalm-pure
     *
     * @param callable(RegisterCleanup): \Generator<A> $generator
     *
     * @return self<A>
     */
    public static function of(callable $generator): self
    {
        return self::distinct(new Sequence\Lazy($generator));
    }

    public function size(): int
    {
        return $this->values->size();
    }

    public function count(): int
    {
        return $this->values->size();
    }

    /**
     * @return \Iterator<int, T>
     */
    public function iterator(): \Iterator
    {
        return $this->values->iterator();
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function intersect(Implementation $set): self
    {
        if ($this === $set) {
            // this is necessary as the manipulation of the same iterator below
            // leads to unexpected behaviour
            return $this;
        }

        return new self($this->values->intersect($set->sequence()));
    }

    /**
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->values->contains($element);
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function remove($element): self
    {
        return new self($this->values->filter(
            static fn($value) => $value !== $element,
        ));
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function diff(Implementation $set): self
    {
        return new self($this->values->diff($set->sequence()));
    }

    /**
     * @param Implementation<T> $set
     */
    public function equals(Implementation $set): bool
    {
        if ($this->size() !== $set->size()) {
            return false;
        }

        return $this->intersect($set)->size() === $this->size();
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        return new self($this->values->filter($predicate));
    }

    /**
     * @param callable(T): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->values->foreach($function);
    }

    /**
     * @template D
     *
     * @param callable(T): D $discriminator
     *
     * @return Map<D, Set<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        return $this
            ->values
            ->groupBy($discriminator)
            ->map(static fn(mixed $_, $sequence) => Set::of(...$sequence->toList()));
    }

    /**
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return self<S>
     */
    public function map(callable $function): self
    {
        return self::distinct($this->values->map($function));
    }

    /**
     * @template S
     *
     * @param callable(T): Set<S> $map
     * @param callable(Set<S>): Sequence\Implementation<S> $exfiltrate
     *
     * @return self<S>
     */
    public function flatMap(callable $map, callable $exfiltrate): self
    {
        return self::distinct($this->values->flatMap($map, $exfiltrate));
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Set<T>>
     */
    public function partition(callable $predicate): Map
    {
        return $this
            ->values
            ->partition($predicate)
            ->map(static fn($_, $sequence) => Set::of(...$sequence->toList()));
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Sequence<T>
     */
    public function sort(callable $function): Sequence
    {
        return $this
            ->values
            ->sort($function)
            ->toSequence();
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function merge(Implementation $set): self
    {
        return self::distinct($this->values->append($set->sequence()));
    }

    /**
     * @template I
     * @template R
     *
     * @param I $carry
     * @param callable(I|R, T): R $reducer
     *
     * @return I|R
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->values->reduce($carry, $reducer);
    }

    /**
     * @return Implementation<T>
     */
    public function clear(): Implementation
    {
        return Primitive::of();
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }

    public function find(callable $predicate): Maybe
    {
        return $this->values->find($predicate);
    }

    /**
     * @template R
     * @param R $carry
     * @param callable(R, T): R $assert
     *
     * @return self<T>
     */
    public function safeguard($carry, callable $assert): self
    {
        return new self($this->values->safeguard($carry, $assert));
    }

    /**
     * @return Sequence\Implementation<T>
     */
    public function sequence(): Sequence\Implementation
    {
        return $this->values;
    }

    /**
     * @return Implementation<T>
     */
    public function memoize(): Implementation
    {
        return new Primitive($this->values->memoize());
    }

    /**
     * @psalm-pure
     */
    private static function distinct(Sequence\Implementation $values): self
    {
        return new self($values->distinct());
    }
}
