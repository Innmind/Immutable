<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    LogicException,
    CannotGroupEmptyStructure,
    ElementNotFound,
    OutOfBoundException,
};

/**
 * @template T
 */
final class Sequence implements \Countable
{
    private string $type;
    private ValidateArgument $validate;
    private Sequence\Implementation $implementation;

    private function __construct(string $type, Sequence\Implementation $implementation)
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
        $self = new self($type, new Sequence\Primitive($type, ...$values));
        $self->implementation->reduce(
            1,
            static function(int $position, $element) use ($self): int {
                ($self->validate)($element, $position);

                return ++$position;
            }
        );

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
        $self = new self('mixed', new Sequence\Primitive('mixed', ...$values));

        return $self;
    }

    /**
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        /** @var self<int> */
        $self = new self('int', new Sequence\Primitive('int', ...$values));

        return $self;
    }

    /**
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        /** @var self<float> */
        $self = new self('float', new Sequence\Primitive('float', ...$values));

        return $self;
    }

    /**
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        /** @var self<string> */
        $self = new self('string', new Sequence\Primitive('string', ...$values));

        return $self;
    }

    /**
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        /** @var self<object> */
        $self = new self('object', new Sequence\Primitive('object', ...$values));

        return $self;
    }

    public function isOfType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Type of the elements
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
     * Return the element at the given index
     *
     * @throws OutOfBoundException
     *
     * @return T
     */
    public function get(int $index)
    {
        /** @var T */
        return $this->implementation->get($index);
    }

    /**
     * Return the diff between this sequence and another
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function diff(self $sequence): self
    {
        assertSequence($this->type, $sequence, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->diff(
            $sequence->implementation
        );

        return $self;
    }

    /**
     * Remove all duplicates from the sequence
     *
     * @return self<T>
     */
    public function distinct(): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->distinct();

        return $self;
    }

    /**
     * Remove the n first elements
     *
     * @return self<T>
     */
    public function drop(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->drop($size);

        return $self;
    }

    /**
     * Remove the n last elements
     *
     * @return self<T>
     */
    public function dropEnd(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->dropEnd($size);

        return $self;
    }

    /**
     * Check if the two sequences are equal
     *
     * @param self<T> $sequence
     */
    public function equals(self $sequence): bool
    {
        assertSequence($this->type, $sequence, 1);

        return $this->implementation->equals(
            $sequence->implementation
        );
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
        $self = clone $this;
        $self->implementation = $this->implementation->filter($predicate);

        return $self;
    }

    /**
     * Apply the given function to all elements of the sequence
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
     * Return the first element
     *
     * @return T
     */
    public function first()
    {
        /** @var T */
        return $this->implementation->first();
    }

    /**
     * Return the last element
     *
     * @return T
     */
    public function last()
    {
        /** @var T */
        return $this->implementation->last();
    }

    /**
     * Check if the sequence contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool
    {
        ($this->validate)($element, 1);

        return $this->implementation->contains($element);
    }

    /**
     * Return the index for the given element
     *
     * @param T $element
     *
     * @throws ElementNotFound
     */
    public function indexOf($element): int
    {
        ($this->validate)($element, 1);

        return $this->implementation->indexOf($element);
    }

    /**
     * Return the list of indices
     *
     * @return self<int>
     */
    public function indices(): self
    {
        /** @var self<int> */
        $self = new self('int', $this->implementation->indices());

        return $self;
    }

    /**
     * Return a new sequence by applying the given function to all elements
     *
     * @param callable(T): T $function
     *
     * @return self<T>
     */
    public function map(callable $function): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->map($function);

        return $self;
    }

    /**
     * Pad the sequence to a defined size with the given element
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function pad(int $size, $element): self
    {
        ($this->validate)($element, 2);

        $self = clone $this;
        $self->implementation = $this->implementation->pad($size, $element);

        return $self;
    }

    /**
     * Return a sequence of 2 sequences partitioned according to the given predicate
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
     * Slice the sequence
     *
     * @return self<T>
     */
    public function slice(int $from, int $until): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->slice($from, $until);

        return $self;
    }

    /**
     * Split the sequence in a sequence of 2 sequences splitted at the given position
     *
     * @throws OutOfBoundException
     *
     * @return self<self<T>>
     */
    public function splitAt(int $position): self
    {
        return $this->implementation->splitAt($position);
    }

    /**
     * Return a sequence with the n first elements
     *
     * @return self<T>
     */
    public function take(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->take($size);

        return $self;
    }

    /**
     * Return a sequence with the n last elements
     *
     * @return self<T>
     */
    public function takeEnd(int $size): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->takeEnd($size);

        return $self;
    }

    /**
     * Append the given sequence to the current one
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function append(self $sequence): self
    {
        assertSequence($this->type, $sequence, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->append(
            $sequence->implementation
        );

        return $self;
    }

    /**
     * Return a sequence with all elements from the current one that exist
     * in the given one
     *
     * @param self<T> $sequence
     *
     * @return self<T>
     */
    public function intersect(self $sequence): self
    {
        assertSequence($this->type, $sequence, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->intersect(
            $sequence->implementation
        );

        return $self;
    }

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str
    {
        return $this->implementation->join($separator);
    }

    /**
     * Add the given element at the end of the sequence
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
     * when constructing the sequence
     *
     * Example:
     * <code>
     * Sequence::of('int')(1)(3)
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
     * Sort the sequence in a different order
     *
     * @param callable(T, T): int $function
     *
     * @return self<T>
     */
    public function sort(callable $function): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->sort($function);

        return $self;
    }

    /**
     * Reduce the sequence to a single value
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
        $self->implementation = new Sequence\Primitive($this->type);

        return $self;
    }

    /**
     * Return the same sequence but in reverse order
     *
     * @return self<T>
     */
    public function reverse(): self
    {
        $self = clone $this;
        $self->implementation = $this->implementation->reverse();

        return $self;
    }

    public function empty(): bool
    {
        return $this->implementation->empty();
    }

    /**
     * @template ST
     *
     * @param callable(T): \Generator<ST> $mapper
     *
     * @return self<ST>
     */
    public function toSequenceOf(string $type, callable $mapper): self
    {
        return $this->implementation->toSequenceOf($type, $mapper);
    }

    /**
     * @template ST
     *
     * @param callable(T): \Generator<ST> $mapper
     *
     * @return Set<ST>
     */
    public function toSetOf(string $type, callable $mapper): Set
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
