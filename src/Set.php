<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\InvalidArgumentException;

final class Set implements \Countable
{
    private string $type;
    private SpecificationInterface $spec;
    private Stream $values;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $type)
    {
        $this->type = $type;
        $this->spec = Type::of($type);
        $this->values = new Stream($type);
    }

    public static function of(string $type, ...$values): self
    {
        $self = new self($type);
        $self->values = Stream::of($type, ...$values)->distinct();

        return $self;
    }

    /**
     * @return self<mixed>
     */
    public static function mixed(...$values): self
    {
        $self = new self('mixed');
        $self->values = Stream::mixed(...$values)->distinct();

        return $self;
    }

    /**
     * @return self<int>
     */
    public static function ints(int ...$values): self
    {
        $self = new self('int');
        $self->values = Stream::ints(...$values)->distinct();

        return $self;
    }

    /**
     * @return self<float>
     */
    public static function floats(float ...$values): self
    {
        $self = new self('float');
        $self->values = Stream::floats(...$values)->distinct();

        return $self;
    }

    /**
     * @return self<string>
     */
    public static function strings(string ...$values): self
    {
        $self = new self('string');
        $self->values = Stream::strings(...$values)->distinct();

        return $self;
    }

    /**
     * @return self<object>
     */
    public static function objects(object ...$values): self
    {
        $self = new self('object');
        $self->values = Stream::objects(...$values)->distinct();

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
        $this->validate($set);

        $newSet = clone $this;
        $newSet->values = $this->values->intersect(
            Stream::of($this->type, ...$set->toArray())
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
        $this->spec->validate($element);

        if ($this->contains($element)) {
            return $this;
        }

        $set = clone $this;
        $set->values = $this->values->add($element);

        return $set;
    }

    /**
     * Check if the set contains the given element
     *
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->values->contains($element);
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
        if (!$this->contains($element)) {
            return $this;
        }

        $index = $this->values->indexOf($element);
        $set = clone $this;
        $set->values = $this
            ->values
            ->clear()
            ->append($this->values->slice(0, $index))
            ->append($this->values->slice($index + 1, $this->size()));

        return $set;
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
        $this->validate($set);

        $newSet = clone $this;
        $newSet->values = $this->values->diff(
            Stream::of($this->type, ...$set->toArray())
        );

        return $newSet;
    }

    /**
     * Check if the given set is identical to this one
     *
     * @param self<T> $set
     */
    public function equals(self $set): bool
    {
        $this->validate($set);

        if ($this->size() !== $set->size()) {
            return false;
        }

        return $this->intersect($set)->size() === $this->size();
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
        $set->values = $this->values->filter($predicate);

        return $set;
    }

    /**
     * Apply the given function to all elements of the set
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
     * @return Map<mixed, self<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        $map = $this->values->groupBy($discriminator);

        return $map->reduce(
            new Map($map->keyType(), Set::class),
            function(Map $carry, $key, Stream $values): Map {
                $set = $this->clear();
                $set->values = $values;

                return $carry->put($key, $set);
            }
        );
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
        return $this->reduce(
            $this->clear(),
            function(self $carry, $value) use ($function): self {
                return $carry->add($function($value));
            }
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
        $truthy = $this->clear();
        $falsy = $this->clear();
        $partitions = $this->values->partition($predicate);
        $truthy->values = $partitions->get(true);
        $falsy->values = $partitions->get(false);

        return Map::of('bool', Set::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * Concatenate all elements with the given separator
     */
    public function join(string $separator): Str
    {
        return $this->values->join($separator);
    }

    /**
     * Return a sequence sorted with the given function
     *
     * @param callable(T, T): int $function
     *
     * @return Stream<T>
     */
    public function sort(callable $function): Stream
    {
        return $this->values->sort($function);
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
        $this->validate($set);

        return $set->reduce(
            $this,
            function(self $carry, $value): self {
                return $carry->add($value);
            }
        );
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
        $self->values = $this->values->clear();

        return $self;
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }

    /**
     * Make sure the set is compatible with the current one
     *
     * @throws InvalidArgumentException
     */
    private function validate(self $set): void
    {
        if (!$set->isOfType($this->type)) {
            throw new InvalidArgumentException(
                'The 2 sets does not reference the same type'
            );
        }
    }
}
