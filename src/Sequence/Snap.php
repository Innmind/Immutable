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
    private Implementation $implementation;
    /** @var pure-Closure(Implementation): Implementation<T> */
    private \Closure $will;

    /**
     * @param ?pure-Closure(Implementation): Implementation<T> $will
     */
    public function __construct(
        Implementation $implementation,
        ?\Closure $will = null,
    ) {
        $this->implementation = $implementation;
        $this->will = $will ?? static fn(Implementation $sequence): Implementation => $sequence;
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function __invoke($element): Implementation
    {
        return $this->will(static fn($self) => $self($element));
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
     * @param int<0, max> $index
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
        return $this->will(static fn($self) => $self->diff($sequence));
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function distinct(): Implementation
    {
        return $this->will(static fn($self) => $self->distinct());
    }

    /**
     * @param int<0, max> $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function drop(int $size): Implementation
    {
        return $this->will(static fn($self) => $self->drop($size));
    }

    /**
     * @param int<0, max> $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function dropEnd(int $size): Implementation
    {
        return $this->will(static fn($self) => $self->dropEnd($size));
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
        return $this->will(static fn($self) => $self->filter($predicate));
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
     * @return Maybe<int<0, max>>
     */
    #[\Override]
    public function indexOf($element): Maybe
    {
        return $this->memoize()->indexOf($element);
    }

    /**
     * Return the list of indices
     *
     * @return Implementation<int<0, max>>
     */
    #[\Override]
    public function indices(): Implementation
    {
        return $this->will(static fn($self) => $self->indices());
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
        return $this->will(static fn($self) => $self->map($function));
    }

    /**
     * @template S
     * @template C of Sequence<S>|Set<S>
     *
     * @param callable(T): C $map
     * @param callable(C): Implementation<S> $exfiltrate
     *
     * @return Implementation<S>
     */
    #[\Override]
    public function flatMap(callable $map, callable $exfiltrate): Implementation
    {
        return $this->will(static fn($self) => $self->flatMap($map, $exfiltrate));
    }

    /**
     * @template S
     *
     * @param callable(Implementation<T>): Sequence<S> $map
     *
     * @return Sequence<S>
     */
    #[\Override]
    public function via(callable $map): Sequence
    {
        // If this snapped version has been loaded then there is no need to keep
        // wrapping the new transformations inside a Snap.
        if ($this->implementation instanceof Primitive) {
            return $this->implementation->via($map);
        }

        $self = $this;

        // $map is not directly called on $this to allow to keep the lazyness or
        // deferredness of the underlying sequence.
        // If multiple snapped steps are composed together, this implementation
        // will recursively forward the ->via call until it reaches a lazy or
        // deferred sequence. But instead of calling the $map with its own
        // implementation it will be called with $this. It means that the user
        // $map function is indeed called with a snapped sequence, and all
        // steps will be memoized when the user finally tries to extract values.
        return $this
            ->implementation
            ->via(static fn() => $map($self))
            ->snap();
    }

    /**
     * @param int<0, max> $size
     * @param T $element
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function pad(int $size, $element): Implementation
    {
        return $this->will(static fn($self) => $self->pad($size, $element));
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
     * @param int<0, max> $from
     * @param int<0, max> $until
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function slice(int $from, int $until): Implementation
    {
        return $this->will(static fn($self) => $self->slice($from, $until));
    }

    /**
     * @param int<0, max> $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function take(int $size): Implementation
    {
        return $this->will(static fn($self) => $self->take($size));
    }

    /**
     * @param int<0, max> $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function takeEnd(int $size): Implementation
    {
        return $this->will(static fn($self) => $self->takeEnd($size));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function append(Implementation $sequence): Implementation
    {
        return $this->will(static fn($self) => $self->append($sequence));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function prepend(Implementation $sequence): Implementation
    {
        return $this->will(static fn($self) => $self->prepend($sequence));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function intersect(Implementation $sequence): Implementation
    {
        return $this->will(static fn($self) => $self->intersect($sequence));
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function sort(callable $function): Implementation
    {
        return $this->will(static fn($self) => $self->sort($function));
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
        return $this->will(static fn($self) => $self->reverse());
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
     * @return Set<T>
     */
    #[\Override]
    public function toSet(): Set
    {
        // If this snapped version has been loaded then there is no need to go
        // back to a lazy Set
        if ($this->implementation instanceof Primitive) {
            return $this->implementation->toSet();
        }

        $self = $this;

        // By using a lazy Set we're sure that we don't load the data too early.
        // And the source of the generator is $this, meaning that as soon the
        // Set starts to load a value it memoize $this (thus loading everything
        // in memory). And all new iterations over the Set will reuse the
        // already loaded data.
        return Set::lazy(static function() use ($self) {
            yield from $self->iterator();
        })->snap();
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
     * @return Implementation<array{T, S}>
     */
    #[\Override]
    public function zip(Implementation $sequence): Implementation
    {
        return $this->will(static fn($self) => $self->zip($sequence));
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
        return $this->will(static fn($self) => $self->safeguard($carry, $assert));
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
        return $this->will(static fn($self) => $self->aggregate($map, $exfiltrate));
    }

    /**
     * @return Primitive<T>
     */
    #[\Override]
    public function memoize(): Primitive
    {
        if ($this->implementation instanceof Primitive) {
            return $this->implementation;
        }

        // By overwriting the property with the memoized version of the data it
        // allows to free the previous object from memory if the user doesn't
        // reference it. If the user does, then it's still kept in memory and
        // memoized itself due to the ->memoize() call before applying the
        // action on this version.
        /** @psalm-suppress InaccessibleProperty */
        $this->implementation = ($this->will)($this->implementation->memoize());
        // Overwriting the action here allows to free from memory any variable
        // captured in the user provided callable.
        /** @psalm-suppress InaccessibleProperty */
        $this->will = static fn(Implementation $sequence): Implementation => $sequence;

        /** @var Primitive<T> */
        return $this->implementation;
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function dropWhile(callable $condition): Implementation
    {
        return $this->will(static fn($self) => $self->dropWhile($condition));
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function takeWhile(callable $condition): Implementation
    {
        return $this->will(static fn($self) => $self->takeWhile($condition));
    }

    /**
     * @template S
     *
     * @param pure-Closure(Implementation<T>): Implementation<S> $method
     *
     * @return Implementation<S>
     */
    private function will(\Closure $method): Implementation
    {
        if ($this->implementation instanceof Primitive) {
            return $method($this->implementation);
        }

        return new self($this, $method);
    }
}
