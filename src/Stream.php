<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    LogicException,
    GroupEmptySequenceException,
};

/**
 * @template T
 */
final class Stream implements \Countable
{
    private string $type;
    private ValidateArgument $validate;
    private Stream\Implementation $implementation;

    private function __construct(string $type)
    {
        $this->type = $type;
        $this->validate = Type::of($type);
        $this->implementation = new Stream\Primitive($type);
    }

    /**
     * @param T $values
     */
    public static function of(string $type, ...$values): self
    {
        $self = new self($type);
        $self->implementation = new Stream\Primitive($type, ...$values);
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
        $self = new self('mixed');
        $self->implementation = new Stream\Primitive('mixed', ...$values);

        return $self;
    }

    /**
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        $self = new self('int');
        $self->implementation = new Stream\Primitive('int', ...$values);

        return $self;
    }

    /**
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        $self = new self('float');
        $self->implementation = new Stream\Primitive('float', ...$values);

        return $self;
    }

    /**
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        $self = new self('string');
        $self->implementation = new Stream\Primitive('string', ...$values);

        return $self;
    }

    /**
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        $self = new self('object');
        $self->implementation = new Stream\Primitive('object', ...$values);

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
     * @return list<T>
     */
    public function toArray(): array
    {
        return $this->implementation->toArray();
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
        return $this->implementation->get($index);
    }

    /**
     * Return the diff between this stream and another
     *
     * @param self<T> $stream
     *
     * @return self<T>
     */
    public function diff(self $stream): self
    {
        assertStream($this->type, $stream, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->diff(
            $stream->implementation
        );

        return $self;
    }

    /**
     * Remove all duplicates from the stream
     *
     * @return self<T>
     */
    public function distinct(): self
    {
        $stream = clone $this;
        $stream->implementation = $this->implementation->distinct();

        return $stream;
    }

    /**
     * Remove the n first elements
     *
     * @return self<T>
     */
    public function drop(int $size): self
    {
        $stream = clone $this;
        $stream->implementation = $this->implementation->drop($size);

        return $stream;
    }

    /**
     * Remove the n last elements
     *
     * @return self<T>
     */
    public function dropEnd(int $size): self
    {
        $stream = clone $this;
        $stream->implementation = $this->implementation->dropEnd($size);

        return $stream;
    }

    /**
     * Check if the two streams are equal
     *
     * @param self<T> $stream
     */
    public function equals(self $stream): bool
    {
        assertStream($this->type, $stream, 1);

        return $this->implementation->equals(
            $stream->implementation
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
        $stream = clone $this;
        $stream->implementation = $this->implementation->filter($predicate);

        return $stream;
    }

    /**
     * Apply the given function to all elements of the stream
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
     * @throws GroupEmptySequenceException
     *
     * @return Map<mixed, self<T>>
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
        return $this->implementation->first();
    }

    /**
     * Return the last element
     *
     * @return T
     */
    public function last()
    {
        return $this->implementation->last();
    }

    /**
     * Check if the stream contains the given element
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
     * @throws ElementNotFoundException
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
        $self = new self('int');
        $self->implementation = $this->implementation->indices();

        return $self;
    }

    /**
     * Return a new stream by applying the given function to all elements
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
     * Pad the stream to a defined size with the given element
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function pad(int $size, $element): self
    {
        ($this->validate)($element, 2);

        $stream = clone $this;
        $stream->implementation = $this->implementation->pad($size, $element);

        return $stream;
    }

    /**
     * Return a stream of 2 streams partitioned according to the given predicate
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
     * Slice the stream
     *
     * @return self<T>
     */
    public function slice(int $from, int $until): self
    {
        $stream = clone $this;
        $stream->implementation = $this->implementation->slice($from, $until);

        return $stream;
    }

    /**
     * Split the stream in a stream of 2 streams splitted at the given position
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
     * Return a stream with the n first elements
     *
     * @return self<T>
     */
    public function take(int $size): self
    {
        $stream = clone $this;
        $stream->implementation = $this->implementation->take($size);

        return $stream;
    }

    /**
     * Return a stream with the n last elements
     *
     * @return self<T>
     */
    public function takeEnd(int $size): self
    {
        $stream = clone $this;
        $stream->implementation = $this->implementation->takeEnd($size);

        return $stream;
    }

    /**
     * Append the given stream to the current one
     *
     * @param self<T> $stream
     *
     * @return self<T>
     */
    public function append(self $stream): self
    {
        assertStream($this->type, $stream, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->append(
            $stream->implementation
        );

        return $self;
    }

    /**
     * Return a stream with all elements from the current one that exist
     * in the given one
     *
     * @param self<T> $stream
     *
     * @return self<T>
     */
    public function intersect(self $stream): self
    {
        assertStream($this->type, $stream, 1);

        $self = clone $this;
        $self->implementation = $this->implementation->intersect(
            $stream->implementation
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
     * Add the given element at the end of the stream
     *
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self
    {
        ($this->validate)($element, 1);

        $stream = clone $this;
        $stream->implementation = $this->implementation->add($element);

        return $stream;
    }

    /**
     * Alias for add method in order to have a syntax similar to a true tuple
     * when constructing the stream
     *
     * Example:
     * <code>
     * Stream::of('int')(1)(3)
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
     * Sort the stream in a different order
     *
     * @param callable(T, T): int $function
     *
     * @return self<T>
     */
    public function sort(callable $function): self
    {
        $stream = clone $this;
        $stream->implementation = $this->implementation->sort($function);

        return $stream;
    }

    /**
     * Reduce the stream to a single value
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
        $self->implementation = new Stream\Primitive($this->type);

        return $self;
    }

    /**
     * Return the same stream but in reverse order
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
}
