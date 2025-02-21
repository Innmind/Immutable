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
final class Primitive implements Implementation
{
    /** @var list<T> */
    private array $values;

    /**
     * @param list<T> $values
     */
    public function __construct(array $values = [])
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
        $values = $this->values;
        $values[] = $element;

        return new self($values);
    }

    #[\Override]
    public function size(): int
    {
        return \count($this->values);
    }

    #[\Override]
    public function count(): int
    {
        return $this->size();
    }

    /**
     * @return \Iterator<T>
     */
    #[\Override]
    public function iterator(): \Iterator
    {
        return new \ArrayIterator($this->values);
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function get(int $index): Maybe
    {
        if (!$this->has($index)) {
            /** @var Maybe<T> */
            return Maybe::nothing();
        }

        return Maybe::just($this->values[$index]);
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return self<T>
     */
    #[\Override]
    public function diff(Implementation $sequence): self
    {
        return $this->filter(static function(mixed $value) use ($sequence): bool {
            /** @var T $value */
            return !$sequence->contains($value);
        });
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function distinct(): self
    {
        return $this->reduce(
            $this->clear(),
            static function(self $values, mixed $value): self {
                /** @var T $value */
                if ($values->contains($value)) {
                    return $values;
                }

                return ($values)($value);
            },
        );
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function drop(int $size): self
    {
        return new self(\array_slice($this->values, $size));
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function dropEnd(int $size): self
    {
        return new self(\array_slice($this->values, 0, $this->size() - $size));
    }

    /**
     * @param Implementation<T> $sequence
     */
    #[\Override]
    public function equals(Implementation $sequence): bool
    {
        $other = [];

        foreach ($sequence->iterator() as $value) {
            $other[] = $value;
        }

        return $this->values === $other;
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    #[\Override]
    public function filter(callable $predicate): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(\array_values(\array_filter(
            $this->values,
            $predicate,
        )));
    }

    /**
     * @param callable(T): void $function
     */
    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        foreach ($this->values as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            $function($value);
        }

        return new SideEffect;
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
        $groups = Map::of();

        foreach ($this->values as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            $key = $discriminator($value);

            /** @var Sequence<T> */
            $group = $groups->get($key)->match(
                static fn($group) => $group,
                static fn() => Sequence::of(),
            );
            $groups = ($groups)($key, ($group)($value));
        }

        /** @var Map<D, Sequence<T>> */
        return $groups;
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function first(): Maybe
    {
        return $this->get(0);
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function last(): Maybe
    {
        return $this->get($this->size() - 1);
    }

    /**
     * @param T $element
     */
    #[\Override]
    public function contains($element): bool
    {
        return \in_array($element, $this->values, true);
    }

    /**
     * @param T $element
     *
     * @return Maybe<0|positive-int>
     */
    #[\Override]
    public function indexOf($element): Maybe
    {
        $index = \array_search($element, $this->values, true);

        if ($index === false) {
            /** @var Maybe<0|positive-int> */
            return Maybe::nothing();
        }

        /** @var Maybe<0|positive-int> */
        return Maybe::just($index);
    }

    /**
     * @psalm-suppress LessSpecificImplementedReturnType Don't why it complains
     *
     * @return self<int>
     */
    #[\Override]
    public function indices(): self
    {
        if ($this->empty()) {
            /** @var self<int> */
            return new self;
        }

        /** @var self<int> */
        return new self(\range(0, $this->size() - 1));
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(\array_map($function, $this->values));
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
        /**
         * @psalm-suppress MixedArgument
         * @psalm-suppress InvalidArgument
         * @psalm-suppress ImpureFunctionCall
         * @var Implementation<S>
         */
        return $this->reduce(
            new self,
            static fn(self $carry, $value) => $carry->append($exfiltrate($map($value))),
        );
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    #[\Override]
    public function pad(int $size, $element): self
    {
        return new self(\array_pad($this->values, $size, $element));
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Sequence<T>>
     */
    #[\Override]
    public function partition(callable $predicate): Map
    {
        /** @var list<T> */
        $truthy = [];
        /** @var list<T> */
        $falsy = [];

        foreach ($this->values as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            if ($predicate($value) === true) {
                $truthy[] = $value;
            } else {
                $falsy[] = $value;
            }
        }

        $true = Sequence::of(...$truthy);
        $false = Sequence::of(...$falsy);

        return Map::of([true, $true], [false, $false]);
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function slice(int $from, int $until): self
    {
        return new self(\array_slice(
            $this->values,
            $from,
            $until - $from,
        ));
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function take(int $size): self
    {
        return $this->slice(0, $size);
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function takeEnd(int $size): self
    {
        return $this->slice($this->size() - $size, $this->size());
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return self<T>
     */
    #[\Override]
    public function append(Implementation $sequence): self
    {
        $other = [];

        foreach ($sequence->iterator() as $value) {
            $other[] = $value;
        }

        return new self(\array_merge($this->values, $other));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return self<T>
     */
    #[\Override]
    public function prepend(Implementation $sequence): self
    {
        $other = [];

        foreach ($sequence->iterator() as $value) {
            $other[] = $value;
        }

        return new self(\array_merge($other, $this->values));
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return self<T>
     */
    #[\Override]
    public function intersect(Implementation $sequence): self
    {
        return $this->filter(static function(mixed $value) use ($sequence): bool {
            /** @var T $value */
            return $sequence->contains($value);
        });
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return self<T>
     */
    #[\Override]
    public function sort(callable $function): self
    {
        $self = clone $this;
        /**
         * @psalm-suppress InaccessibleProperty
         * @psalm-suppress ImpureFunctionCall
         */
        \usort($self->values, $function);

        return $self;
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
        /**
         * @psalm-suppress ImpureFunctionCall
         * @var R
         */
        return \array_reduce($this->values, $reducer, $carry);
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
        $continuation = Sink\Continuation::of($carry);

        foreach ($this->values as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            $continuation = $reducer($carry, $value, $continuation);
            $carry = $continuation->unwrap();

            if (!$continuation->shouldContinue()) {
                break;
            }
        }

        return $continuation->unwrap();
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function clear(): Implementation
    {
        return new self;
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function reverse(): self
    {
        return new self(\array_reverse($this->values));
    }

    #[\Override]
    public function empty(): bool
    {
        return !$this->has(0);
    }

    #[\Override]
    public function toIdentity(): Identity
    {
        /** @var Identity<Implementation<T>> */
        return Identity::of($this);
    }

    /**
     * @return Sequence<T>
     */
    #[\Override]
    public function toSequence(): Sequence
    {
        return Sequence::of(...$this->values);
    }

    /**
     * @return Set<T>
     */
    #[\Override]
    public function toSet(): Set
    {
        return Set::of(...$this->values);
    }

    #[\Override]
    public function find(callable $predicate): Maybe
    {
        foreach ($this->values as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            if ($predicate($value) === true) {
                return Maybe::just($value);
            }
        }

        /** @var Maybe<T> */
        return Maybe::nothing();
    }

    #[\Override]
    public function match(callable $wrap, callable $match, callable $empty)
    {
        /** @psalm-suppress MixedArgument */
        return $this
            ->first()
            ->match(
                fn($first) => $match($first, $wrap($this->drop(1))),
                $empty,
            );
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
        /** @var list<array{T, S}> */
        $values = [];
        $other = $sequence->iterator();

        foreach ($this->iterator() as $value) {
            /** @psalm-suppress ImpureMethodCall */
            if (!$other->valid()) {
                break;
            }

            /** @psalm-suppress ImpureMethodCall */
            $values[] = [$value, $other->current()];
            /** @psalm-suppress ImpureMethodCall */
            $other->next();
        }

        /** @var Implementation<array{T, S}> */
        return new self($values);
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
        $_ = $this->reduce($carry, $assert);

        return $this;
    }

    /**
     * @template A
     *
     * @param callable(T|A, T): Sequence<A> $map
     * @param callable(Sequence<A>): Implementation<A> $exfiltrate
     *
     * @return self<T|A>
     */
    #[\Override]
    public function aggregate(callable $map, callable $exfiltrate): self
    {
        $aggregate = new Aggregate($this->iterator());
        /** @psalm-suppress MixedArgument */
        $values = $aggregate(static fn($a, $b) => $exfiltrate($map($a, $b))->iterator());
        $aggregated = [];

        /** @psalm-suppress ImpureMethodCall */
        foreach ($values as $value) {
            $aggregated[] = $value;
        }

        return new self($aggregated);
    }

    /**
     * @return self<T>
     */
    #[\Override]
    public function memoize(): self
    {
        return $this;
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    #[\Override]
    public function dropWhile(callable $condition): self
    {
        $values = [];
        $iterator = $this->iterator();

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            /**
             * @psalm-suppress ImpureMethodCall
             * @psalm-suppress ImpureFunctionCall
             * @psalm-suppress PossiblyNullArgument
             */
            if (!$condition($iterator->current())) {
                /** @psalm-suppress ImpureMethodCall */
                $values[] = $iterator->current();
                /** @psalm-suppress ImpureMethodCall */
                $iterator->next();

                break;
            }

            /** @psalm-suppress ImpureMethodCall */
            $iterator->next();
        }

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            /** @psalm-suppress ImpureMethodCall */
            $values[] = $iterator->current();
            /** @psalm-suppress ImpureMethodCall */
            $iterator->next();
        }

        return new self($values);
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    #[\Override]
    public function takeWhile(callable $condition): self
    {
        $values = [];

        foreach ($this->iterator() as $current) {
            /** @psalm-suppress ImpureFunctionCall */
            if (!$condition($current)) {
                break;
            }

            $values[] = $current;
        }

        return new self($values);
    }

    private function has(int $index): bool
    {
        return \array_key_exists($index, $this->values);
    }
}
