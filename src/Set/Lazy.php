<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Type,
    Str,
    Exception\CannotGroupEmptyStructure,
};

/**
 * @template T
 */
final class Lazy implements Implementation
{
    /** @var Sequence\Implementation<T> */
    private Sequence\Implementation $values;

    /**
     * @param callable(): \Generator<T> $generator
     */
    public function __construct(callable $generator)
    {
        /** @var Sequence\Implementation<T> */
        $this->values = (new Sequence\Lazy($generator))->distinct();
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        $set = clone $this;
        $set->values = ($this->values)($element)->distinct();

        return $set;
    }

    public function size(): int
    {
        return $this->values->size();
    }

    public function count(): int
    {
        return $this->values->size();
    }

    /**
     * @return \Iterator<T>
     */
    public function iterator(): \Iterator
    {
        return $this->values->iterator();
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function intersect(Implementation $set): self
    {
        if ($this === $set) {
            // this is necessary as the manipulation of the same iterator below
            // leads to unexpected behaviour
            return $this;
        }

        $self = clone $this;
        $self->values = $this->values->intersect(
            new Sequence\Defer(
                (static function(\Iterator $values): \Generator {
                    /** @var T $value */
                    foreach ($values as $value) {
                        yield $value;
                    }
                })($set->iterator()),
            ),
        );

        return $self;
    }

    /**
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->values->contains($element);
    }

    /**
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
            ->slice(0, $index)
            ->append($this->values->slice($index + 1, $this->size()));

        return $set;
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function diff(Implementation $set): self
    {
        $self = clone $this;
        $self->values = $this->values->diff(
            new Sequence\Defer(
                (static function(\Iterator $values): \Generator {
                    /** @var T $value */
                    foreach ($values as $value) {
                        yield $value;
                    }
                })($set->iterator()),
            ),
        );

        return $self;
    }

    /**
     * @param Implementation<T> $set
     */
    public function equals(Implementation $set): bool
    {
        if ($this->size() !== $set->size()) {
            return false;
        }

        return $this->intersect($set)->size() === $this->size();
    }

    /**
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
     * @param callable(T): void $function
     */
    public function foreach(callable $function): void
    {
        $this->values->foreach($function);
    }

    /**
     * @template D
     * @param callable(T): D $discriminator
     *
     * @throws CannotGroupEmptyStructure
     *
     * @return Map<D, Set<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        $map = $this->values->groupBy($discriminator);

        /**
         * @psalm-suppress MissingParamType
         * @var Map<D, Set<T>>
         */
        return $map->reduce(
            Map::of(),
            fn(Map $carry, $key, Sequence $values): Map => ($carry)(
                $key,
                $values->toSetOf('T'),
            ),
        );
    }

    /**
     * @param callable(T): T $function
     *
     * @return self<T>
     */
    public function map(callable $function): self
    {
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $function = static function($value) use ($function) {
            /** @var T $value */
            return $function($value);
        };

        $self = clone $this;
        $self->values = $this
            ->values
            ->map($function)
            ->distinct();

        return $self;
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Set<T>>
     */
    public function partition(callable $predicate): Map
    {
        $partitions = $this->values->partition($predicate);
        /** @var Set<T> */
        $truthy = $partitions
            ->get(true)
            ->toSetOf('T');
        /** @var Set<T> */
        $falsy = $partitions
            ->get(false)
            ->toSetOf('T');

        /**
         * @psalm-suppress InvalidScalarArgument
         * @psalm-suppress InvalidArgument
         * @var Map<bool, Set<T>>
         */
        return Map::of()
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Sequence<T>
     */
    public function sort(callable $function): Sequence
    {
        return $this
            ->values
            ->sort($function)
            ->toSequenceOf('T');
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function merge(Implementation $set): self
    {
        $self = clone $this;
        $self->values = new Sequence\Defer(
            (static function(\Iterator $self, \Iterator $set): \Generator {
                /** @var T $value */
                foreach ($self as $value) {
                    yield $value;
                }

                /** @var T $value */
                foreach ($set as $value) {
                    yield $value;
                }
            })($this->values->iterator(), $set->iterator()),
        );
        $self->values = $self->values->distinct();

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
        return $this->values->reduce($carry, $reducer);
    }

    /**
     * @return Implementation<T>
     */
    public function clear(): Implementation
    {
        return new Primitive;
    }

    public function empty(): bool
    {
        return $this->values->empty();
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
        return $this->values->toSequenceOf($type, $mapper);
    }

    /**
     * @template ST
     *
     * @param null|callable(T): \Generator<ST> $mapper
     *
     * @return Set<ST>
     */
    public function toSetOf(string $type, callable $mapper = null): Set
    {
        return $this->values->toSetOf($type, $mapper);
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
        return $this->values->toMapOf($key, $value, $mapper);
    }

    public function find(callable $predicate)
    {
        return $this->values->find($predicate);
    }
}
