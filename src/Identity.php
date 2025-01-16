<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Identity\{
    Implementation,
    InMemory,
    Lazy,
    Defer,
};

/**
 * @psalm-immutable
 * @template T
 */
final class Identity
{
    /** @var Implementation<T> */
    private Implementation $implementation;

    /**
     * @param Implementation<T> $implementation
     */
    private function __construct(Implementation $implementation)
    {
        $this->implementation = $implementation;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    public static function of(mixed $value): self
    {
        return new self(new InMemory($value));
    }

    /**
     * When using a lazy computation all transformations via map and flatMap
     * will be applied when calling unwrap. Each call to unwrap will call again
     * all transformations.
     *
     * @psalm-pure
     * @template A
     *
     * @param callable(): A $value
     *
     * @return self<A>
     */
    public static function lazy(callable $value): self
    {
        return new self(new Lazy($value));
    }

    /**
     * When using a deferred computation all transformations via map and flatMap
     * will be applied when calling unwrap. The value is computed once and all
     * calls to unwrap will return the same value.
     *
     * @psalm-pure
     * @template A
     *
     * @param callable(): A $value
     *
     * @return self<A>
     */
    public static function defer(callable $value): self
    {
        return new self(new Defer($value));
    }

    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    public function map(callable $map): self
    {
        return new self($this->implementation->map($map));
    }

    /**
     * @template U
     *
     * @param callable(T): self<U> $map
     *
     * @return self<U>
     */
    public function flatMap(callable $map): self
    {
        return $this->implementation->flatMap($map);
    }

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence
    {
        return $this->implementation->toSequence();
    }

    /**
     * @return T
     */
    public function unwrap(): mixed
    {
        return $this->implementation->unwrap();
    }
}
