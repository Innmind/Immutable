<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\CannotGroupEmptyStructure;

final class Set implements \Countable
{
    private string $type;
    private ValidateArgument $validate;
    private Set\Implementation $implementation;

    /**
     * {@inheritdoc}
     */
    private function __construct(string $type)
    {
        $this->type = $type;
        $this->validate = Type::of($type);
        $this->implementation = new Set\Primitive($type);
    }

    public static function of(string $type, ...$values): self
    {
        $self = new self($type);
        $self->implementation = new Set\Primitive($type, ...$values);

        return $self;
    }

    /**
     * @return self<mixed>
     */
    public static function mixed(...$values): self
    {
        $self = new self('mixed');
        $self->implementation = new Set\Primitive('mixed', ...$values);

        return $self;
    }

    /**
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        $self = new self('int');
        $self->implementation = new Set\Primitive('int', ...$values);

        return $self;
    }

    /**
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        $self = new self('float');
        $self->implementation = new Set\Primitive('float', ...$values);

        return $self;
    }

    /**
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        $self = new self('string');
        $self->implementation = new Set\Primitive('string', ...$values);

        return $self;
    }

    /**
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        $self = new self('object');
        $self->implementation = new Set\Primitive('object', ...$values);

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

    public function toArray(): array
    {
        return $this->implementation->toArray();
    }

    /**
     * Intersect this set with the given one
     *
     * @param self<T> $set
     *
     * @throws InvalidArgumentException If the sets are not of the same type
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
     * @param callable(T) $discriminator
     *
     * @throws CannotGroupEmptyStructure
     *
     * @return Map<mixed, self<T>>
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
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str
    {
        return $this->implementation->join($separator);
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
     * @param mixed $carry
     * @param callable(mixed, T) $reducer
     *
     * @return mixed
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
}
