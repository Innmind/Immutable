<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @template-covariant T
 * @psalm-immutable
 */
final class Set implements \Countable
{
    /** @var Set\Implementation<T> */
    private Set\Implementation $implementation;

    private function __construct(Set\Implementation $implementation)
    {
        $this->implementation = $implementation;
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
        return new self(($this->implementation)($element));
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
        return new self(Set\Primitive::of(...$values));
    }

    /**
     * It will load the values inside the generator only upon the first use
     * of the set
     *
     * Use this mode when the amount of data may not fit in memory
     *
     * @template V
     * @psalm-pure
     *
     * @param \Generator<V> $generator
     *
     * @return self<V>
     */
    public static function defer(\Generator $generator): self
    {
        return new self(Set\Defer::of($generator));
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
        return new self(Set\Lazy::of($generator));
    }

    /**
     * @no-named-arguments
     * @psalm-pure
     *
     * @return self<mixed>
     */
    public static function mixed(mixed ...$values): self
    {
        return new self(Set\Primitive::of(...$values));
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
        $self = new self(Set\Primitive::of(...$values));

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
        $self = new self(Set\Primitive::of(...$values));

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
        $self = new self(Set\Primitive::of(...$values));

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
        $self = new self(Set\Primitive::of(...$values));

        return $self;
    }

    /**
     * @return 0|positive-int
     */
    public function size(): int
    {
        return $this->implementation->size();
    }

    /**
     * @return 0|positive-int
     */
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
        return new self($this->implementation->remove($element));
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
        return $this->implementation->equals($set->implementation);
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
        return $this->implementation->groupBy($discriminator);
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
        return new self($this->implementation->map($function));
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
        /** @var callable(self<S>): Sequence\Implementation<S> */
        $exfiltrate = static fn(self $set): Sequence\Implementation => $set->implementation->sequence();

        return new self($this->implementation->flatMap($map, $exfiltrate));
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
        return $this->implementation->partition($predicate);
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
     * Create a new set with elements of both sets
     *
     * @param self<T> $set
     *
     * @return self<T>
     */
    public function merge(self $set): self
    {
        return new self($this->implementation->merge(
            $set->implementation,
        ));
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
        return new self($this->implementation->clear());
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
        /** @psalm-suppress MixedArgument For some reason Psalm no longer recognize the type of $first */
        return $this->implementation->sequence()->match(
            static fn($sequence) => new self(new Set\Primitive($sequence)),
            static fn($first, $rest) => $match($first, $rest),
            $empty,
        );
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function matches(callable $predicate): bool
    {
        /** @psalm-suppress MixedArgument For some reason Psalm no longer recognize the type in `find` */
        return $this
            ->find(static fn($value) => !$predicate($value))
            ->match(
                static fn() => false,
                static fn() => true,
            );
    }

    /**
     * @param callable(T): bool $predicate
     */
    public function any(callable $predicate): bool
    {
        return $this->find($predicate)->match(
            static fn() => true,
            static fn() => false,
        );
    }

    /**
     * @return list<T>
     */
    public function toList(): array
    {
        /** @var list<T> */
        $all = [];

        /** @var list<T> */
        return $this->reduce(
            $all,
            static function(array $carry, $value): array {
                /** @var T $value */
                $carry[] = $value;

                return $carry;
            },
        );
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
}
