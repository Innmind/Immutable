<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Maybe,
    SideEffect,
    Identity,
};

/**
 * @template T
 * @implements Implementation<T>
 * @psalm-immutable
 */
final class Snap implements Implementation
{
    /** @var Implementation<T> */
    private Implementation $will;
    /** @var ?Implementation<T> */
    private ?Implementation $snapshot;

    /**
     * @param Implementation<T> $will
     */
    public function __construct(Implementation $will)
    {
        $this->will = $will;
        $this->snapshot = null;
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function __invoke($element): Implementation
    {
        return new self(($this->will)($element));
    }

    #[\Override]
    public function size(): int
    {
        return $this->memoize()->size();
    }

    #[\Override]
    public function count(): int
    {
        return $this->size();
    }

    /**
     * @return Iterator<T>
     */
    #[\Override]
    public function iterator(): Iterator
    {
        return $this->memoize()->iterator();
    }

    /**
     * @param 0|positive-int $index
     *
     * @return Maybe<T>
     */
    #[\Override]
    public function get(int $index): Maybe
    {
        return $this->memoize()->get($index);
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function diff(Implementation $sequence): Implementation
    {
        return new self($this->will->diff($sequence));
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function distinct(): Implementation
    {
        return new self($this->will->distinct());
    }

    /**
     * @param 0|positive-int $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function drop(int $size): Implementation
    {
        return new self($this->will->drop($size));
    }

    /**
     * @param 0|positive-int $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function dropEnd(int $size): Implementation
    {
        return new self($this->will->dropEnd($size));
    }

    /**
     * @param Implementation<T> $sequence
     */
    #[\Override]
    public function equals(Implementation $sequence): bool
    {
        return $this->memoize()->equals($sequence);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        return new self($this->will->filter($predicate));
    }

    /**
     * @param callable(T): void $function
     */
    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        return $this->memoize()->foreach($function);
    }

    /**
     * @template D
     * @param callable(T): D $discriminator
     *
     * @return Map<D, Sequence<T>>
     */
    #[\Override]
    public function groupBy(callable $discriminator): Map
    {
        /** @var Map<D, Sequence<T>> */
        return $this->memoize()->groupBy($discriminator);
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function first(): Maybe
    {
        return $this->memoize()->first();
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function last(): Maybe
    {
        return $this->memoize()->last();
    }

    /**
     * @param T $element
     */
    #[\Override]
    public function contains($element): bool
    {
        return $this->memoize()->contains($element);
    }

    /**
     * @param T $element
     *
     * @return Maybe<0|positive-int>
     */
    #[\Override]
    public function indexOf($element): Maybe
    {
        return $this->memoize()->indexOf($element);
    }

    /**
     * Return the list of indices
     *
     * @return Implementation<0|positive-int>
     */
    #[\Override]
    public function indices(): Implementation
    {
        return new self($this->will->indices());
    }

    /**
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return Implementation<S>
     */
    #[\Override]
    public function map(callable $function): Implementation
    {
        return new self($this->will->map($function));
    }

    /**
     * @template S
     * @template C of Sequence<S>|Set<S>
     *
     * @param callable(T): C $map
     * @param callable(C): Implementation<S> $exfiltrate
     *
     * @return self<S>
     */
    #[\Override]
    public function flatMap(callable $map, callable $exfiltrate): self
    {
        return new self($this->will->flatMap($map, $exfiltrate));
    }

    /**
     * @param 0|positive-int $size
     * @param T $element
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function pad(int $size, $element): Implementation
    {
        return new self($this->will->pad($size, $element));
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Sequence<T>>
     */
    #[\Override]
    public function partition(callable $predicate): Map
    {
        /** @var Map<bool, Sequence<T>> */
        return $this->memoize()->partition($predicate);
    }

    /**
     * @param 0|positive-int $from
     * @param 0|positive-int $until
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function slice(int $from, int $until): Implementation
    {
        return new self($this->will->slice($from, $until));
    }

    /**
     * @param 0|positive-int $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function take(int $size): Implementation
    {
        return new self($this->will->take($size));
    }

    /**
     * @param 0|positive-int $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function takeEnd(int $size): Implementation
    {
        return new self($this->will->takeEnd($size));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function append(Implementation $sequence): Implementation
    {
        return new self($this->will->append($sequence));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function prepend(Implementation $sequence): Implementation
    {
        return new self($this->will->prepend($sequence));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function intersect(Implementation $sequence): Implementation
    {
        return new self($this->will->intersect($sequence));
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function sort(callable $function): Implementation
    {
        return new self($this->will->sort($function));
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
        return $this->memoize()->reduce($carry, $reducer);
    }

    /**
     * @template I
     *
     * @param I $carry
     * @param callable(I, T, Sink\Continuation<I>): Sink\Continuation<I> $reducer
     *
     * @return I
     */
    #[\Override]
    public function sink($carry, callable $reducer): mixed
    {
        return $this->memoize()->sink($carry, $reducer);
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function clear(): Implementation
    {
        return new Primitive;
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function reverse(): Implementation
    {
        return new self($this->will->reverse());
    }

    #[\Override]
    public function empty(): bool
    {
        return $this->memoize()->empty();
    }

    #[\Override]
    public function toIdentity(): Identity
    {
        return $this->memoize()->toIdentity();
    }

    /**
     * @return Sequence<T>
     */
    #[\Override]
    public function toSequence(): Sequence
    {
        return $this->toSequence()->snap();
    }

    /**
     * @return Set<T>
     */
    #[\Override]
    public function toSet(): Set
    {
        return $this->toSet()->snap();
    }

    #[\Override]
    public function find(callable $predicate): Maybe
    {
        return $this->memoize()->find($predicate);
    }

    #[\Override]
    public function match(callable $wrap, callable $match, callable $empty)
    {
        return $this->memoize()->match($wrap, $match, $empty);
    }

    /**
     * @template S
     *
     * @param Implementation<S> $sequence
     *
     * @return self<array{T, S}>
     */
    #[\Override]
    public function zip(Implementation $sequence): self
    {
        return new self($this->will->zip($sequence));
    }

    /**
     * @template R
     * @param R $carry
     * @param callable(R, T): R $assert
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function safeguard($carry, callable $assert): Implementation
    {
        return new self($this->will->safeguard($carry, $assert));
    }

    /**
     * @template A
     *
     * @param callable(T|A, T): Sequence<A> $map
     * @param callable(Sequence<A>): Implementation<A> $exfiltrate
     *
     * @return Implementation<T|A>
     */
    #[\Override]
    public function aggregate(callable $map, callable $exfiltrate): Implementation
    {
        return new self($this->will->aggregate($map, $exfiltrate));
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function memoize(): Implementation
    {
        /** @psalm-suppress InaccessibleProperty */
        return $this->snapshot ??= $this->will->memoize();
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function dropWhile(callable $condition): Implementation
    {
        return new self($this->will->dropWhile($condition));
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function takeWhile(callable $condition): Implementation
    {
        return new self($this->will->takeWhile($condition));
    }
}
