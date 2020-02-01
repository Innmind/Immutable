<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Type,
    ValidateArgument,
    Str,
    Exception\CannotGroupEmptyStructure,
};

/**
 * @template T
 */
final class Lazy implements Implementation
{
    private string $type;
    private ValidateArgument $validate;
    private Sequence\Implementation $values;

    /**
     * @param callable(): \Generator<T> $generator
     */
    public function __construct(string $type, callable $generator)
    {
        $this->type = $type;
        $this->validate = Type::of($type);
        $this->values = (new Sequence\Lazy($type, $generator))->distinct();
    }

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
                $this->type,
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
     *
     * @return self<T>
     */
    public function __invoke($element): self
    {
        $set = clone $this;
        $set->values = ($this->values)($element)->distinct();

        return $set;
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
                $this->type,
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
        /** @var Map<D, Sequence<T>> */
        $map = $this->values->groupBy($discriminator);

        /**
         * @psalm-suppress MixedReturnTypeCoercion
         * @var Map<D, Set<T>>
         */
        return $map->reduce(
            Map::of($map->keyType(), Set::class),
            fn(Map $carry, $key, Sequence $values): Map => ($carry)(
                $key,
                $values->toSetOf($this->type),
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
        $validate = $this->validate;

        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         */
        $function = static function($value) use ($validate, $function) {
            /** @var T $value */
            $returned = $function($value);
            ($validate)($returned, 1);

            return $returned;
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
            ->toSetOf($this->type);
        /** @var Set<T> */
        $falsy = $partitions
            ->get(false)
            ->toSetOf($this->type);

        /**
         * @psalm-suppress InvalidScalarArgument
         * @psalm-suppress InvalidArgument
         * @var Map<bool, Set<T>>
         */
        return Map::of('bool', Set::class)
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
            ->toSequenceOf($this->type);
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
            $this->type,
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
        return new Primitive($this->type);
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
