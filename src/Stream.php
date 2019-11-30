<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    LogicException,
    GroupEmptySequenceException,
    InvalidArgumentException
};

/**
 * {@inheritdoc}
 */
final class Stream implements \Countable
{
    private string $type;
    private SpecificationInterface $spec;
    private Sequence $values;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->spec = Type::of($type);
        $this->values = new Sequence;
    }

    /**
     * @param T $values
     */
    public static function of(string $type, ...$values): self
    {
        $self = new self($type);
        $self->values = new Sequence(...$values);
        $self->values->foreach(static function($element) use ($self): void {
            $self->spec->validate($element);
        });

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
        return $this->values->size();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->values->size();
    }

    public function toArray(): array
    {
        return $this->values->toArray();
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
        return $this->values->get($index);
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
        $this->validate($stream);

        $newStream = clone $this;
        $newStream->values = $this->values->diff(
            new Sequence(...$stream->toArray())
        );

        return $newStream;
    }

    /**
     * Remove all duplicates from the stream
     *
     * @return self<T>
     */
    public function distinct(): self
    {
        $stream = clone $this;
        $stream->values = $this->values->distinct();

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
        $stream->values = $this->values->drop($size);

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
        $stream->values = $this->values->dropEnd($size);

        return $stream;
    }

    /**
     * Check if the two streams are equal
     *
     * @param self<T> $stream
     */
    public function equals(self $stream): bool
    {
        $this->validate($stream);

        return $this->values->equals(
            new Sequence(...$stream->toArray())
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
        $stream->values = $this->values->filter($predicate);

        return $stream;
    }

    /**
     * Apply the given function to all elements of the stream
     *
     * @param callable(T): void $function
     *
     * @return self<T>
     */
    public function foreach(callable $function): void
    {
        $this->values->foreach($function);
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
        if ($this->size() === 0) {
            throw new GroupEmptySequenceException;
        }

        $map = null;

        foreach ($this->values->toArray() as $value) {
            $key = $discriminator($value);

            if ($map === null) {
                $map = new Map(
                    Type::determine($key),
                    self::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->add($value)
                );
            } else {
                $map = $map->put(
                    $key,
                    (new self($this->type))->add($value)
                );
            }
        }

        return $map;
    }

    /**
     * Return the first element
     *
     * @return T
     */
    public function first()
    {
        return $this->values->first();
    }

    /**
     * Return the last element
     *
     * @return T
     */
    public function last()
    {
        return $this->values->last();
    }

    /**
     * Check if the stream contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->values->contains($element);
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
        return $this->values->indexOf($element);
    }

    /**
     * Return the list of indices
     *
     * @return self<int>
     */
    public function indices(): self
    {
        return $this->values->indices();
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
        $self->values = $this->values->map($function);
        $self->values->foreach(function($element): void {
            $this->spec->validate($element);
        });

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
        $this->spec->validate($element);

        $stream = clone $this;
        $stream->values = $this->values->pad($size, $element);

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
        $truthy = [];
        $falsy = [];

        foreach ($this->values->toArray() as $value) {
            if ($predicate($value) === true) {
                $truthy[] = $value;
            } else {
                $falsy[] = $value;
            }
        }

        $true = $this->clear();
        $true->values = new Sequence(...$truthy);
        $false = $this->clear();
        $false->values = new Sequence(...$falsy);

        return Map::of('bool', self::class)
            (true, $true)
            (false, $false);
    }

    /**
     * Slice the stream
     *
     * @return self<T>
     */
    public function slice(int $from, int $until): self
    {
        $stream = clone $this;
        $stream->values = $this->values->slice($from, $until);

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
        $stream = new self(self::class);
        $splitted = $this->values->splitAt($position);
        $first = new self($this->type);
        $second = new self($this->type);
        $first->values = $splitted->first();
        $second->values = $splitted->last();

        return $stream->add($first)->add($second);
    }

    /**
     * Return a stream with the n first elements
     *
     * @return self<T>
     */
    public function take(int $size): self
    {
        $stream = clone $this;
        $stream->values = $this->values->take($size);

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
        $stream->values = $this->values->takeEnd($size);

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
        $this->validate($stream);

        $self = clone $this;
        $self->values = $this->values->append(
            new Sequence(...$stream->toArray())
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
        $this->validate($stream);

        $self = clone $this;
        $self->values = $this->values->intersect(
            new Sequence(...$stream->toArray())
        );

        return $self;
    }

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str
    {
        return new Str((string) $this->values->join($separator));
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
        $this->spec->validate($element);

        $stream = clone $this;
        $stream->values = $this->values->add($element);

        return $stream;
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
        $stream->values = $this->values->sort($function);

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
        return $this->values->reduce($carry, $reducer);
    }

    /**
     * Return a set of the same type but without any value
     *
     * @return self<T>
     */
    public function clear(): self
    {
        $self = clone $this;
        $self->values = new Sequence;

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
        $self->values = $this->values->reverse();

        return $self;
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }

    /**
     * Make sure the stream is compatible with the current one
     *
     * @throws InvalidArgumentException
     */
    private function validate(self $stream): void
    {
        if (!$stream->isOfType($this->type)) {
            throw new InvalidArgumentException(
                'The 2 streams does not reference the same type'
            );
        }
    }
}
