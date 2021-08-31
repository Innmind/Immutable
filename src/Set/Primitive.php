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
 * @psalm-immutable
 */
final class Primitive implements Implementation
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
        if ($this->contains($element)) {
            return $this;
        }

        return new self(($this->values)($element));
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @template A
     *
     * @param A $values
     *
     * @return self<A>
     */
    public static function of(...$values): self
    {
        return new self((new Sequence\Primitive($values))->distinct());
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self($this->values->intersect(
            new Sequence\Primitive(\array_values(\iterator_to_array($set->iterator()))),
        ));
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
        if (!$this->contains($element)) {
            return $this;
        }

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
        /** @psalm-suppress ImpureFunctionCall */
        return new self($this->values->diff(
            new Sequence\Primitive(\array_values(\iterator_to_array($set->iterator()))),
        ));
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
        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         */
        return $this->reduce(
            self::of(),
            static fn(self $carry, $value): self => ($carry)($function($value)),
        );
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
        /** @psalm-suppress MixedArgument For some reason it no longer recognize the template for $value */
        return $set->reduce(
            $this,
            static fn(self $carry, $value): self => ($carry)($value),
        );
    }

    /**
     * @template R
     * @param R $carry
     * @param callable(R, T): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->values->reduce($carry, $reducer);
    }

    /**
     * @return self<T>
     */
    public function clear(): self
    {
        return self::of();
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }

    public function find(callable $predicate): Maybe
    {
        return $this->values->find($predicate);
    }
}
