<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Type,
    Str,
    Maybe,
};

/**
 * @template T
 */
final class Primitive implements Implementation
{
    /** @var Sequence\Implementation<T> */
    private Sequence\Implementation $values;

    /**
     * @no-named-arguments
     *
     * @param T $values
     */
    public function __construct(...$values)
    {
        /** @var Sequence\Implementation<T> */
        $this->values = (new Sequence\Primitive(...$values))->distinct();
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

        $set = clone $this;
        $set->values = ($this->values)($element);

        return $set;
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
        $self = $this->clear();
        $self->values = $this->values->intersect(
            new Sequence\Primitive(...$set->iterator()),
        );

        return $self;
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

        $set = clone $this;
        $set->values = $this
            ->values
            ->filter(static fn($value) => $value !== $element);

        return $set;
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function diff(Implementation $set): self
    {
        $self = clone $this;
        $self->values = $this->values->diff(
            new Sequence\Primitive(...$set->iterator()),
        );

        return $self;
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
        $set = clone $this;
        $set->values = $this->values->filter($predicate);

        return $set;
    }

    /**
     * @param callable(T): void $function
     */
    public function foreach(callable $function): void
    {
        $this->values->foreach($function);
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
            ->map(static fn($_, $sequence) => Set::of(...$sequence->toList()));
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
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         */
        return $this->reduce(
            new self,
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
        return new self;
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
