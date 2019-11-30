<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Exception\OutOfBoundException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptySequenceException
};

/**
 * {@inheritdoc}
 */
final class Sequence implements \Countable
{
    private array $values;
    private ?int $size;

    public function __construct(...$values)
    {
        $this->values = $values;
    }

    public static function of(...$values): self
    {
        $self = new self;
        $self->values = $values;

        return $self;
    }

    public function size(): int
    {
        return $this->size ?? $this->size = \count($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->size();
    }

    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Return the element at the given index
     *
     * @throws OutOfBoundException
     *
     * @return mixed
     */
    public function get(int $index)
    {
        if (!$this->has($index)) {
            throw new OutOfBoundException;
        }

        return $this->values[$index];
    }

    /**
     * Check the index exist
     */
    public function has(int $index): bool
    {
        return \array_key_exists($index, $this->values);
    }

    /**
     * Return the diff between this sequence and another
     */
    public function diff(self $seq): self
    {
        return $this->filter(static function($value) use ($seq): bool {
            return !$seq->contains($value);
        });
    }

    /**
     * Remove all duplicates from the sequence
     */
    public function distinct(): self
    {
        return $this->reduce(
            new self,
            static function(self $values, $value): self {
                if ($values->contains($value)) {
                    return $values;
                }

                return $values->add($value);
            }
        );
    }

    /**
     * Remove the n first elements
     */
    public function drop(int $size): self
    {
        $self = new self;
        $self->values = \array_slice($this->values, $size);

        return $self;
    }

    /**
     * Remove the n last elements
     */
    public function dropEnd(int $size): self
    {
        $self = new self;
        $self->values = \array_slice($this->values, 0, $this->size() - $size);

        return $self;
    }

    /**
     * Check if the two sequences are equal
     */
    public function equals(self $seq): bool
    {
        return $this->values === $seq->toArray();
    }

    /**
     * Return all elements that satisfy the given predicate
     *
     * @param callable(mixed): bool $predicate
     */
    public function filter(callable $predicate): self
    {
        return new self(...\array_filter(
            $this->values,
            $predicate
        ));
    }

    /**
     * Apply the given function to all elements of the sequence
     *
     * @param callable(mixed): void $function
     */
    public function foreach(callable $function): self
    {
        foreach ($this->values as $value) {
            $function($value);
        }

        return $this;
    }

    /**
     * Return a new map of pairs grouped by keys determined with the given
     * discriminator function
     *
     * @param callable(mixed) $discriminator
     *
     * @return Map<mixed, self>
     */
    public function groupBy(callable $discriminator): Map
    {
        if ($this->size() === 0) {
            throw new GroupEmptySequenceException;
        }

        $map = null;

        foreach ($this->values as $value) {
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
                $map = $map->put($key, new self($value));
            }
        }

        return $map;
    }

    /**
     * Return the first element
     *
     * @return mixed
     */
    public function first()
    {
        if ($this->size() === 0) {
            throw new OutOfBoundException;
        }

        return $this->values[0];
    }

    /**
     * Return the last element
     *
     * @return mixed
     */
    public function last()
    {
        if ($this->size() === 0) {
            throw new OutOfBoundException;
        }

        return $this->values[$this->size() - 1];
    }

    /**
     * Check if the sequence contains the given element
     *
     * @param mixed $element
     */
    public function contains($element): bool
    {
        return \in_array($element, $this->values, true);
    }

    /**
     * Return the index for the given element
     *
     * @param mixed $element
     *
     * @throws ElementNotFoundException
     */
    public function indexOf($element): int
    {
        $index = \array_search($element, $this->values, true);

        if ($index === false) {
            throw new ElementNotFoundException;
        }

        return $index;
    }

    /**
     * Return the list of indices
     *
     * @return Stream<int>
     */
    public function indices(): Stream
    {
        if ($this->size() === 0) {
            return Stream::of('int');
        }

        return Stream::of('int', ...\range(0, $this->size() - 1));
    }

    /**
     * Return a new sequence by applying the given function to all elements
     *
     * @param callable(mixed) $function
     */
    public function map(callable $function): self
    {
        $self = clone $this;
        $self->values = \array_map($function, $this->values);

        return $self;
    }

    /**
     * Pad the sequence to a defined size with the given element
     *
     * @param mixed $element
     */
    public function pad(int $size, $element): self
    {
        $self = new self;
        $self->values = \array_pad($this->values, $size, $element);

        return $self;
    }

    /**
     * Return a sequence of 2 sequences partitioned according to the given predicate
     *
     * @param callable(mixed): bool $predicate
     *
     * @return Map<bool, self>
     */
    public function partition(callable $predicate): Map
    {
        $truthy = [];
        $falsy = [];

        foreach ($this->values as $value) {
            if ($predicate($value) === true) {
                $truthy[] = $value;
            } else {
                $falsy[] = $value;
            }
        }

        $true = new self;
        $true->values = $truthy;
        $false = new self;
        $false->values = $falsy;

        return Map::of('bool', self::class)
            (true, $true)
            (false, $false);
    }

    public function slice(int $from, int $until): self
    {
        $self = new self;
        $self->values = \array_slice(
            $this->values,
            $from,
            $until - $from
        );

        return $self;
    }

    /**
     * Split the sequence in a sequence of 2 sequences splitted at the given position
     *
     * @throws OutOfBoundException
     *
     * @return Stream<self>
     */
    public function splitAt(int $index): Stream
    {
        return (new Stream(self::class))
            ->add($this->slice(0, $index))
            ->add($this->slice($index, $this->size()));
    }

    /**
     * Return a sequence with the n first elements
     */
    public function take(int $size): self
    {
        return $this->slice(0, $size);
    }

    /**
     * Return a sequence with the n last elements
     */
    public function takeEnd(int $size): self
    {
        return $this->slice($this->size() - $size, $this->size());
    }

    /**
     * Append the given sequence to the current one
     */
    public function append(self $seq): self
    {
        $self = new self;
        $self->values = \array_merge($this->values, $seq->toArray());

        return $self;
    }

    /**
     * Return a sequence with all elements from the current one that exist
     * in the given one
     */
    public function intersect(self $seq): self
    {
        return $this->filter(static function($value) use ($seq): bool {
            return $seq->contains($value);
        });
    }

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str
    {
        return new Str(\implode($separator, $this->values));
    }

    /**
     * Add the given element at the end of the sequence
     *
     * @param mixed $element
     */
    public function add($element): self
    {
        $self = clone $this;
        $self->values[] = $element;
        $self->size = $this->size() + 1;

        return $self;
    }

    /**
     * Sort the sequence in a different order
     *
     * @param callable(mixed, mixed): int $function
     */
    public function sort(callable $function): self
    {
        $self = clone $this;
        \usort($self->values, $function);

        return $self;
    }

    /**
     * Reduce the sequence to a single value
     *
     * @param mixed $carry
     * @param callable(mixed, mixed) $reducer
     *
     * @return mixed
     */
    public function reduce($carry, callable $reducer)
    {
        return \array_reduce($this->values, $reducer, $carry);
    }

    /**
     * Return the same sequence but in reverse order
     *
     * @return self
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
}
