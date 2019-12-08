<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\CannotGroupEmptyStructure;

/**
 * @template T
 */
final class Set implements \Countable
{
    private string $type;
    private ValidateArgument $validate;
    private Set\Implementation $implementation;

    /**
     * {@inheritdoc}
     */
    private function __construct(string $type, Set\Implementation $implementation)
    {
        $this->type = $type;
        $this->validate = Type::of($type);
        $this->implementation = $implementation;
    }

    /**
     * @param T $values
     *
     * @return self<T>
     */
    public static function of(string $type, ...$values): self
    {
        $self = new self($type, new Set\Primitive($type, ...$values));

        return $self;
    }

    /**
     * It will load the values inside the generator only upon the first use
     * of the set
     *
     * Use this mode when the amount of data may not fit in memory
     *
     * @param \Generator<T> $generator
     *
     * @return self<T>
     */
    public static function defer(string $type, \Generator $generator): self
    {
        $self = new self($type, new Set\Defer($type, $generator));

        return $self;
    }

    /**
     * It will call the given function every time a new operation is done on the
     * set. This means the returned structure may not be truly immutable as
     * between the calls the underlying source may change.
     *
     * Use this mode when calling to an external source (meaning IO bound) such
     * as parsing a file or calling an API
     *
     * @param callable(): \Generator<T> $generator
     *
     * @return self<T>
     */
    public static function lazy(string $type, callable $generator): self
    {
        $self = new self($type, new Set\Lazy($type, $generator));

        return $self;
    }

    /**
     * @param mixed $values
     *
     * @return self<mixed>
     */
    public static function mixed(...$values): self
    {
        /** @var self<mixed> */
        $self = new self('mixed', new Set\Primitive('mixed', ...$values));

        return $self;
    }

    /**
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        /** @var self<int> */
        $self = new self('int', new Set\Primitive('int', ...$values));

        return $self;
    }

    /**
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        /** @var self<float> */
        $self = new self('float', new Set\Primitive('float', ...$values));

        return $self;
    }

    /**
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        /** @var self<string> */
        $self = new self('string', new Set\Primitive('string', ...$values));

        return $self;
    }

    /**
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        /** @var self<object> */
        $self = new self('object', new Set\Primitive('object', ...$values));

        return $self;
    }

    public function isOfType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Return the type of this set
     */
    public function type(): string
    {
        return $this->type;
    }

    public function size(): int
    {
        return $this->implementation->size();
    }

    /**
     * {@inheritdoc}
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
        assertSet($this->type, $set, 1);

        $newSet = clone $this;
        $newSet->implementation = $this->implementation->intersect(
            $set->implementation
        );

        return $newSet;
    }

    /**
     * Add a element to the set
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self
    {
        ($this->validate)($element, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->add($element);

        return $self;
    }

    /**
     * Alias for add method in order to have a syntax similar to a true tuple
     * when constructing the set
     *
     * Example:
     * <code>
     * Set::of('int')(1)(3)
     * </code>
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        return $this->add($element);
    }

    /**
     * Check if the set contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool
    {
        ($this->validate)($element, 1);

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
        ($this->validate)($element, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->remove($element);

        return $self;
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
        assertSet($this->type, $set, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->diff(
            $set->implementation
        );

        return $self;
    }

    /**
     * Check if the given set is identical to this one
     *
     * @param self<T> $set
     */
    public function equals(self $set): bool
    {
        assertSet($this->type, $set, 1);

        return $this->implementation->equals($set->implementation);
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
        $set = clone $this;
        $set->implementation = $this->implementation->filter($predicate);

        return $set;
    }

    /**
     * Apply the given function to all elements of the set
     *
     * @param callable(T): void $function
     */
    public function foreach(callable $function): void
    {
        $this->implementation->foreach($function);
    }

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @template D
     * @param callable(T): D $discriminator
     *
     * @throws CannotGroupEmptyStructure
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
     * @param callable(T): T $function
     *
     * @return self<T>
     */
    public function map(callable $function): self
    {
        $self = $this->clear();
        $self->implementation = $this->implementation->map($function);

        return $self;
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
        assertSet($this->type, $set, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->merge(
            $set->implementation
        );

        return $self;
    }

    /**
     * Reduce the set to a single value
     *
     * @template R
     * @param R $carry
     * @param callable(R, T): R $reducer
     *
     * @return R
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
        $self = clone $this;
        $self->implementation = $this->implementation->clear();

        return $self;
    }

    public function empty(): bool
    {
        return $this->implementation->empty();
    }

    /**
     * @template ST
     *
     * @param null|callable(T): \Generator<ST> $mapper
     *
     * @return Sequence<ST>
     */
    public function toSequenceOf(string $type, callable $mapper = null): Sequence
    {
        return $this->implementation->toSequenceOf($type, $mapper);
    }

    /**
     * @template ST
     *
     * @param null|callable(T): \Generator<ST> $mapper
     *
     * @return self<ST>
     */
    public function toSetOf(string $type, callable $mapper = null): self
    {
        return $this->implementation->toSetOf($type, $mapper);
    }



    /**
     * @template MT
     * @template MS
     *
     * @param callable(T): \Generator<MT, MS> $mapper
     *
     * @return Map<MT, MS>
     */
    public function toMapOf(string $key, string $value, callable $mapper): Map
    {
        return $this->implementation->toMapOf($key, $value, $mapper);
    }
}
