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
final class Primitive implements Implementation
{
    private string $type;
    private ValidateArgument $validate;
    private Sequence $values;

    /**
     * @param T $values
     */
    public function __construct(string $type, ...$values)
    {
        $this->type = $type;
        $this->validate = Type::of($type);
        $this->values = Sequence::of($type, ...$values)->distinct();
    }

    public function isOfType(string $type): bool
    {
        return $this->type === $type;
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
     * @return list<T>
     */
    public function toArray(): array
    {
        return $this->values->toArray();
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function intersect(Implementation $set): self
    {
        $self = $this->clear();
        $self->values = $this->values->intersect(
            Sequence::of($this->type, ...$set->toArray())
        );

        return $self;
    }

    /**
     * @param T $element
     *
     * @return self<T>
     */
    public function add($element): self
    {
        if ($this->contains($element)) {
            return $this;
        }

        $set = clone $this;
        $set->values = ($this->values)($element);

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
            ->clear()
            ->append($this->values->slice(0, $index))
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
            Sequence::of($this->type, ...$set->toArray())
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
            function(Map $carry, $key, Sequence $values): Map {
                return ($carry)(
                    $key,
                    Set::of($this->type, ...$values->toArray()),
                );
            }
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
        $function = function($value) use ($function) {
            /** @var T $value */
            $returned = $function($value);
            ($this->validate)($returned, 1);

            return $returned;
        };

        return $this->reduce(
            $this->clear(),
            function(self $carry, $value) use ($function): self {
                return $carry->add($function($value));
            }
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
        $truthy = Set::of($this->type, ...$partitions->get(true)->toArray());
        /** @var Set<T> */
        $falsy = Set::of($this->type, ...$partitions->get(false)->toArray());

        /**
         * @psalm-suppress InvalidScalarArgument
         * @psalm-suppress InvalidArgument
         */
        return Map::of('bool', Set::class)
            (true, $truthy)
            (false, $falsy);
    }

    public function join(string $separator): Str
    {
        return $this->values->join($separator);
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Sequence<T>
     */
    public function sort(callable $function): Sequence
    {
        return $this->values->sort($function);
    }

    /**
     * @param Implementation<T> $set
     *
     * @return self<T>
     */
    public function merge(Implementation $set): self
    {
        return $set->reduce(
            $this,
            function(self $carry, $value): self {
                return $carry->add($value);
            }
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
     * @return self<T>
     */
    public function clear(): self
    {
        return new self($this->type);
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }
}
