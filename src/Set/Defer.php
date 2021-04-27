<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Type,
    Str,
};

/**
 * @template T
 */
final class Defer implements Implementation
{
    /** @var Sequence\Implementation<T> */
    private Sequence\Implementation $values;

    /**
     * @param Sequence\Implementation<T> $values
     */
    public function __construct(Sequence\Implementation $values)
    {
        $this->values = $values->distinct();
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        return new self(($this->values)($element));
    }

    /**
     * @template A
     *
     * @param \Generator<A> $generator
     *
     * @return self<A>
     */
    public static function of(\Generator $generator): self
    {
        return new self(new Sequence\Defer($generator));
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
     * @return \Iterator<int, T>
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

        return new self(
            $this->values->intersect(
                new Sequence\Defer(
                    (static function(\Iterator $values): \Generator {
                        /** @var T $value */
                        foreach ($values as $value) {
                            yield $value;
                        }
                    })($set->iterator()),
                ),
            ),
        );
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

        return new self(
            $this
                ->values
                ->slice(0, $index)
                ->append($this->values->slice($index + 1, $this->size())),
        );
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function diff(Implementation $set): self
    {
        return new self(
            $this->values->diff(
                new Sequence\Defer(
                    (static function(\Iterator $values): \Generator {
                        /** @var T $value */
                        foreach ($values as $value) {
                            yield $value;
                        }
                    })($set->iterator()),
                ),
            ),
        );
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
        return new self($this->values->filter($predicate));
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
     *
     * @param callable(T): D $discriminator
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
            static fn(Map $carry, $key, Sequence $values): Map => ($carry)(
                $key,
                $values->toSetOf('T'),
            ),
        );
    }

    /**
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
                ->values
                ->map($function)
                ->distinct(),
        );
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
        return new self(
            new Sequence\Defer(
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
            ),
        );
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
