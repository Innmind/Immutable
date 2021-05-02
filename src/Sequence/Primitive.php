<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Set,
    Maybe,
};

/**
 * @template T
 */
final class Primitive implements Implementation
{
    /** @var list<T> */
    private array $values;
    private ?int $size = null;

    /**
     * @no-named-arguments
     *
     * @param T $values
     */
    public function __construct(...$values)
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
        $self = clone $this;
        $self->values[] = $element;
        $self->size = $this->size() + 1;

        return $self;
    }

    public function size(): int
    {
        return $this->size ?? $this->size = \count($this->values);
    }

    public function count(): int
    {
        return $this->size();
    }

    /**
     * @return \Iterator<int, T>
     */
    public function iterator(): \Iterator
    {
        return new \ArrayIterator($this->values);
    }

    /**
     * @return Maybe<T>
     */
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
    public function diff(Implementation $sequence): self
    {
        /** @psalm-suppress MissingClosureParamType */
        return $this->filter(static function($value) use ($sequence): bool {
            /** @var T $value */
            return !$sequence->contains($value);
        });
    }

    /**
     * @return self<T>
     */
    public function distinct(): self
    {
        /** @psalm-suppress MissingClosureParamType */
        return $this->reduce(
            $this->clear(),
            static function(self $values, $value): self {
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
    public function drop(int $size): self
    {
        $self = $this->clear();
        $self->values = \array_slice($this->values, $size);

        return $self;
    }

    /**
     * @return self<T>
     */
    public function dropEnd(int $size): self
    {
        $self = $this->clear();
        $self->values = \array_slice($this->values, 0, $this->size() - $size);

        return $self;
    }

    /**
     * @param Implementation<T> $sequence
     */
    public function equals(Implementation $sequence): bool
    {
        return $this->values === \iterator_to_array($sequence->iterator());
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        $self = $this->clear();
        $self->values = \array_values(\array_filter(
            $this->values,
            $predicate,
        ));

        return $self;
    }

    /**
     * @param callable(T): void $function
     */
    public function foreach(callable $function): void
    {
        foreach ($this->values as $value) {
            $function($value);
        }
    }

    /**
     * @template D
     * @param callable(T): D $discriminator
     *
     * @return Map<D, Sequence<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        /** @var Map<D, Sequence<T>> */
        $groups = Map::of();

        foreach ($this->values as $value) {
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
    public function first(): Maybe
    {
        return $this->get(0);
    }

    /**
     * @return Maybe<T>
     */
    public function last(): Maybe
    {
        return $this->get($this->size() - 1);
    }

    /**
     * @param T $element
     */
    public function contains($element): bool
    {
        return \in_array($element, $this->values, true);
    }

    /**
     * @param T $element
     *
     * @return Maybe<int>
     */
    public function indexOf($element): Maybe
    {
        $index = \array_search($element, $this->values, true);

        if ($index === false) {
            /** @var Maybe<int> */
            return Maybe::nothing();
        }

        return Maybe::just($index);
    }

    /**
     * @psalm-suppress LessSpecificImplementedReturnType Don't why it complains
     *
     * @return self<int>
     */
    public function indices(): self
    {
        if ($this->empty()) {
            /** @var self<int> */
            return new self;
        }

        /** @var self<int> */
        return new self(...\range(0, $this->size() - 1));
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
        return new self(...\array_map($function, $this->values));
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function pad(int $size, $element): self
    {
        $self = $this->clear();
        $self->values = \array_pad($this->values, $size, $element);

        return $self;
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Sequence<T>>
     */
    public function partition(callable $predicate): Map
    {
        /** @var list<T> */
        $truthy = [];
        /** @var list<T> */
        $falsy = [];

        foreach ($this->values as $value) {
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
    public function slice(int $from, int $until): self
    {
        $self = $this->clear();
        $self->values = \array_slice(
            $this->values,
            $from,
            $until - $from,
        );

        return $self;
    }

    /**
     * @return self<T>
     */
    public function take(int $size): self
    {
        return $this->slice(0, $size);
    }

    /**
     * @return self<T>
     */
    public function takeEnd(int $size): self
    {
        return $this->slice($this->size() - $size, $this->size());
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return self<T>
     */
    public function append(Implementation $sequence): self
    {
        $self = $this->clear();
        $self->values = \array_merge($this->values, \iterator_to_array($sequence->iterator()));

        return $self;
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return self<T>
     */
    public function intersect(Implementation $sequence): self
    {
        /** @psalm-suppress MissingClosureParamType */
        return $this->filter(static function($value) use ($sequence): bool {
            /** @var T $value */
            return $sequence->contains($value);
        });
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return self<T>
     */
    public function sort(callable $function): self
    {
        $self = clone $this;
        \usort($self->values, $function);

        return $self;
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
        /** @var R */
        return \array_reduce($this->values, $reducer, $carry);
    }

    /**
     * @return self<T>
     */
    public function clear(): Implementation
    {
        return new self;
    }

    /**
     * @return self<T>
     */
    public function reverse(): self
    {
        $self = clone $this;
        $self->values = \array_reverse($this->values);

        return $self;
    }

    public function empty(): bool
    {
        return !$this->has(0);
    }

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence
    {
        return Sequence::of(...$this->values);
    }

    /**
     * @return Set<T>
     */
    public function toSet(): Set
    {
        return Set::of(...$this->values);
    }

    public function find(callable $predicate): Maybe
    {
        foreach ($this->values as $value) {
            if ($predicate($value) === true) {
                return Maybe::just($value);
            }
        }

        /** @var Maybe<T> */
        return Maybe::nothing();
    }

    private function has(int $index): bool
    {
        return \array_key_exists($index, $this->values);
    }
}
