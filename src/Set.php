<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @template-covariant T
 * @psalm-immutable
 */
final class Set implements \Countable
{
    /**
     * @param Sequence<T> $implementation
     */
    private function __construct(
        private Sequence $implementation,
    ) {
    }

    /**
     * Add an element to the set
     *
     * Example:
     * <code>
     * Set::of()(1)(3)
     * </code>
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        return new self(($this->implementation)($element)->distinct());
    }

    /**
     * @template V
     * @no-named-arguments
     * @psalm-pure
     *
     * @param V $values
     *
     * @return self<V>
     */
    public static function of(...$values): self
    {
        return new self(Sequence::of(...$values)->distinct());
    }

    /**
     * It will load the values inside the generator only upon the first use
     * of the set
     *
     * Use this mode when the amount of data may not fit in memory
     *
     * @template V
     * @psalm-pure
     * @deprecated You should use ::snap() instead
     *
     * @param \Generator<V> $generator
     *
     * @return self<V>
     */
    public static function defer(\Generator $generator): self
    {
        return new self(Sequence::defer($generator)->distinct());
    }

    /**
     * It will call the given function every time a new operation is done on the
     * set. This means the returned structure may not be truly immutable as
     * between the calls the underlying source may change.
     *
     * Use this mode when calling to an external source (meaning IO bound) such
     * as parsing a file or calling an API
     *
     * @template V
     * @psalm-pure
     *
     * @param callable(RegisterCleanup): \Generator<V> $generator
     *
     * @return self<V>
     */
    public static function lazy(callable $generator): self
    {
        return new self(Sequence::lazy($generator)->distinct());
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<mixed>
     */
    public static function mixed(mixed ...$values): self
    {
        return self::of(...$values);
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        /** @var self<int> */
        $self = self::of(...$values);

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        /** @var self<float> */
        $self = self::of(...$values);

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        /** @var self<string> */
        $self = self::of(...$values);

        return $self;
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        /** @var self<object> */
        $self = self::of(...$values);

        return $self;
    }

    /**
     * @return int<0, max>
     */
    public function size(): int
    {
        return $this->implementation->size();
    }

    /**
     * @return int<0, max>
     */
    #[\Override]
    public function count(): int
    {
        return $this->implementation->size();
    }

    /**
     * Intersect this set with the given one
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function intersect(self $set): self
    {
        if ($this === $set) {
            // this is necessary as the manipulation of the same iterator below
            // leads to unexpected behaviour
            return $this;
        }

        return new self($this->implementation->intersect(
            $set->implementation,
        ));
    }

    /**
     * Add an element to the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self
    {
        return ($this)($element);
    }

    /**
     * Check if the set contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->implementation->contains($element);
    }

    /**
     * Remove the element from the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function remove($element): self
    {
        return new self($this->implementation->filter(
            static fn($value) => $value !== $element,
        ));
    }

    /**
     * Return the diff between this set and the given one
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function diff(self $set): self
    {
        return new self($this->implementation->diff(
            $set->implementation,
        ));
    }

    /**
     * Check if the given set is identical to this one
     *
     * @param self<T> $set
     */
    public function equals(self $set): bool
    {
        if ($this === $set) {
            // This avoids loading a lazy set, as a Set is necessarily equal to
            // itself
            return true;
        }

        $size = $this->size();

        if ($size !== $set->size()) {
            return false;
        }

        return $this->intersect($set)->size() === $size;
    }

    /**
     * This is the same behaviour as `filter` but it allows Psalm to understand
     * the type of the values contained in the returned Set
     *
     * @template S
     *
     * @param Predicate<S> $predicate
     *
     * @return self<S>
     */
    public function keep(Predicate $predicate): self
    {
        /** @var self<S> */
        return $this->filter($predicate);
    }

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        return new self($this->implementation->filter($predicate));
    }

    /**
     * Return all elements that don't satisfy the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function exclude(callable $predicate): self
    {
        /** @psalm-suppress MixedArgument */
        return $this->filter(static fn($value) => !$predicate($value));
    }

    /**
     * Apply the given function to all elements of the set
     *
     * @param callable(T): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->implementation->foreach($function);
    }

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     *
     * @param callable(T): D $discriminator
     *
     * @return Map<D, self<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        return $this
            ->implementation
            ->groupBy($discriminator)
            ->map(static fn(mixed $_, $sequence) => $sequence->toSet());
    }

    /**
     * Return a new set by applying the given function to all elements
     *
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return self<S>
     */
    public function map(callable $function): self
    {
        return new self(
            $this
                ->implementation
                ->map($function)
                ->distinct(),
        );
    }

    /**
     * Merge all sets created by each value from the original set
     *
     * @template S
     *
     * @param callable(T): self<S> $map
     *
     * @return self<S>
     */
    public function flatMap(callable $map): self
    {
        /** @psalm-suppress MixedArgument */
        return new self(
            $this
                ->implementation
                ->flatMap(static fn($value) => $map($value)->unsorted())
                ->distinct(),
        );
    }

    /**
     * Return a sequence of 2 sets partitioned according to the given predicate
     *
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, self<T>>
     */
    public function partition(callable $predicate): Map
    {
        return $this
            ->implementation
            ->partition($predicate)
            ->map(static fn($_, $sequence) => $sequence->toSet());
    }

    /**
     * Return a sequence sorted with the given function
     *
     * @param callable(T, T): int $function
     *
     * @return Sequence<T>
     */
    public function sort(callable $function): Sequence
    {
        return $this->implementation->sort($function);
    }

    /**
     * Return an unsorted sequence
     *
     * @return Sequence<T>
     */
    public function unsorted(): Sequence
    {
        return $this->implementation;
    }

    /**
     * Create a new set with elements of both sets
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function merge(self $set): self
    {
        return new self(
            $this
                ->implementation
                ->append($set->implementation)
                ->distinct(),
        );
    }

    /**
     * Reduce the set to a single value
     *
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
        return $this->implementation->reduce($carry, $reducer);
    }

    /**
     * Return a set of the same type but without any value
     *
     * @return self<T>
     */
    public function clear(): self
    {
        return new self(Sequence::of());
    }

    public function empty(): bool
    {
        return $this->implementation->empty();
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Maybe<T>
     */
    public function find(callable $predicate): Maybe
    {
        return $this->implementation->find($predicate);
    }

    /**
     * @template R
     *
     * @param callable(T, self<T>): R $match
     * @param callable(): R $empty
     *
     * @return R
     */
    public function match(callable $match, callable $empty)
    {
        /** @psalm-suppress MixedArgument */
        return $this->implementation->match(
            static fn($first, $rest) => $match($first, $rest->toSet()),
            $empty,
        );
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function matches(callable $predicate): bool
    {
        return $this->implementation->matches($predicate);
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        return $this->implementation->any($predicate);
    }

    /**
     * @return list<T>
     */
    public function toList(): array
    {
        return $this->implementation->toList();
    }

    /**
     * Make sure every value conforms to the assertion, you must throw an
     * exception when a value does not conform.
     *
     * For deferred and lazy sets the assertion is called on the go, meaning
     * subsequent operations may start before reaching a value that doesn't
     * conform. To be used carefully.
     *
     * @template R
     *
     * @param R $carry
     * @param callable(R, T): R $assert
     *
     * @return self<T>
     */
    public function safeguard($carry, callable $assert)
    {
        return new self($this->implementation->safeguard($carry, $assert));
    }

    /**
     * Force to load all values into memory (only useful for deferred and lazy Set)
     *
     * @return self<T>
     */
    public function memoize(): self
    {
        return new self($this->implementation->memoize());
    }

    /**
     * This method will make sure all the underlying data is loaded when a
     * future method that needs to access data is called.
     *
     * This is similar to ::defer() except it loads everything in memory at once
     * before doing the operation. This avoids to deal with partially loaded
     * iterators that may lead to bugs.
     *
     * @return self<T>
     */
    public function snap(): self
    {
        return new self($this->implementation->snap());
    }
}
