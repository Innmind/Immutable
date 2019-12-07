<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Set,
    Accumulate,
    Exception\LogicException,
    Exception\CannotGroupEmptyStructure,
    Exception\ElementNotFound,
    Exception\OutOfBoundException,
};

/**
 * @template T
 */
final class Defer implements Implementation
{
    private string $type;
    private \Iterator $values;

    public function __construct(string $type, \Generator $generator)
    {
        $this->type = $type;
        $this->values = new Accumulate($generator);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function size(): int
    {
        return $this->load()->size();
    }

    public function count(): int
    {
        return $this->load()->count();
    }

    public function toArray(): array
    {
        return \iterator_to_array($this->values);
    }

    /**
     * @throws OutOfBoundException
     *
     * @return T
     */
    public function get(int $index)
    {
        $iteration = 0;

        /** @var T $value */
        foreach ($this->values as $value) {
            if ($index === $iteration) {
                /** @var T */
                return $value;
            }

            ++$iteration;
        }

        throw new OutOfBoundException;
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    public function diff(Implementation $sequence): Implementation
    {
        return $this->load()->diff($sequence);
    }

    /**
     * @return Implementation<T>
     */
    public function distinct(): Implementation
    {
        return $this->load()->distinct();
    }

    /**
     * @return Implementation<T>
     */
    public function drop(int $size): Implementation
    {
        return $this->load()->drop($size);
    }

    /**
     * @return Implementation<T>
     */
    public function dropEnd(int $size): Implementation
    {
        return $this->load()->dropEnd($size);
    }

    /**
     * @param Implementation<T> $sequence
     */
    public function equals(Implementation $sequence): bool
    {
        return $this->load()->equals($sequence);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Implementation<T>
     */
    public function filter(callable $predicate): Implementation
    {
        return $this->load()->filter($predicate);
    }

    /**
     * @param callable(T): void $function
     */
    public function foreach(callable $function): void
    {
        $this->load()->foreach($function);
    }

    /**
     * @template D
     * @param callable(T): D $discriminator
     *
     * @throws CannotGroupEmptyStructure
     *
     * @return Map<D, Sequence<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        return $this->load()->groupBy($discriminator);
    }

    /**
     * @return T
     */
    public function first()
    {
        return $this->load()->first();
    }

    /**
     * @return T
     */
    public function last()
    {
        return $this->load()->last();
    }

    /**
     * @param T $element
     */
    public function contains($element): bool
    {
        return $this->load()->contains($element);
    }

    /**
     * @param T $element
     *
     * @throws ElementNotFound
     */
    public function indexOf($element): int
    {
        return $this->load()->indexOf($element);
    }

    /**
     * Return the list of indices
     *
     * @return Implementation<int>
     */
    public function indices(): Implementation
    {
        return $this->load()->indices();
    }

    /**
     * @param callable(T): T $function
     *
     * @return Implementation<T>
     */
    public function map(callable $function): Implementation
    {
        return $this->load()->map($function);
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    public function pad(int $size, $element): Implementation
    {
        /** @var Implementation<T> */
        return $this->load()->pad($size, $element);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Sequence<T>>
     */
    public function partition(callable $predicate): Map
    {
        return $this->load()->partition($predicate);
    }

    /**
     * @return Implementation<T>
     */
    public function slice(int $from, int $until): Implementation
    {
        return $this->load()->slice($from, $until);
    }

    /**
     * @throws OutOfBoundException
     *
     * @return Sequence<Sequence<T>>
     */
    public function splitAt(int $position): Sequence
    {
        return $this->load()->splitAt($position);
    }

    /**
     * @return Implementation<T>
     */
    public function take(int $size): Implementation
    {
        return $this->load()->take($size);
    }

    /**
     * @return Implementation<T>
     */
    public function takeEnd(int $size): Implementation
    {
        return $this->load()->takeEnd($size);
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    public function append(Implementation $sequence): Implementation
    {
        return $this->load()->append($sequence);
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    public function intersect(Implementation $sequence): Implementation
    {
        return $this->load()->intersect($sequence);
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    public function add($element): Implementation
    {
        /** @var Implementation<T> */
        return $this->load()->add($element);
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Implementation<T>
     */
    public function sort(callable $function): Implementation
    {
        return $this->load()->sort($function);
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
        return $this->load()->reduce($carry, $reducer);
    }

    /**
     * @return Implementation<T>
     */
    public function clear(): Implementation
    {
        return new Primitive($this->type);
    }

    /**
     * @return Implementation<T>
     */
    public function reverse(): Implementation
    {
        return $this->load()->reverse();
    }

    public function empty(): bool
    {
        return $this->load()->empty();
    }

    /**
     * @template ST
     *
     * @param callable(T): \Generator<ST> $mapper
     *
     * @return Sequence<ST>
     */
    public function toSequenceOf(string $type, callable $mapper): Sequence
    {
        return $this->load()->toSequenceOf($type, $mapper);
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
        return $this->load()->toSetOf($type, $mapper);
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
        return $this->load()->toMapOf($key, $value, $mapper);
    }

    /**
     * @return Implementation<T>
     */
    private function load(): Implementation
    {
        return new Primitive(
            $this->type,
            ...\iterator_to_array($this->values),
        );
    }
}
