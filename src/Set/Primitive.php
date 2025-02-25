<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Maybe,
    SideEffect,
};

/**
 * @template T
 * @implements Implementation<T>
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
    #[\Override]
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

    #[\Override]
    public function size(): int
    {
        return $this->values->size();
    }

    #[\Override]
    public function count(): int
    {
        return $this->values->size();
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    #[\Override]
    public function intersect(Implementation $set): self
    {
        return new self($this->values->intersect($set->sequence()));
    }

    /**
     * @param T $element
     */
    #[\Override]
    public function contains($element): bool
    {
        return $this->values->contains($element);
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    #[\Override]
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
    #[\Override]
    public function diff(Implementation $set): self
    {
        return new self($this->values->diff($set->sequence()));
    }

    /**
     * @param Implementation<T> $set
     */
    #[\Override]
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
    #[\Override]
    public function filter(callable $predicate): self
    {
        return new self($this->values->filter($predicate));
    }

    /**
     * @param callable(T): void $function
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function map(callable $function): self
    {
        return new self($this->values->map($function)->distinct());
    }

    /**
     * @template S
     *
     * @param callable(T): Set<S> $map
     * @param callable(Set<S>): Sequence\Implementation<S> $exfiltrate
     *
     * @return Implementation<S>
     */
    #[\Override]
    public function flatMap(callable $map, callable $exfiltrate): Implementation
    {
        return new self(
            $this
                ->values
                ->flatMap($map, $exfiltrate)
                ->distinct(),
        );
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Set<T>>
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function merge(Implementation $set): self
    {
        return new self($this->values->append($set->sequence())->distinct());
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
    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        return $this->values->reduce($carry, $reducer);
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function clear(): self
    {
        return self::of();
    }

    #[\Override]
    public function empty(): bool
    {
        return $this->values->empty();
    }

    #[\Override]
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
    #[\Override]
    public function safeguard($carry, callable $assert): self
    {
        return new self($this->values->safeguard($carry, $assert));
    }

    /**
     * @return Sequence\Implementation<T>
     */
    #[\Override]
    public function sequence(): Sequence\Implementation
    {
        return $this->values;
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function memoize(): self
    {
        return $this;
    }
}
