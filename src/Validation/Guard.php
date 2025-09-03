<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Validation;

use Innmind\Immutable\Sequence;

/**
 * @internal
 * @template T
 * @psalm-immutable
 */
final class Guard
{
    /**
     * @param Sequence<T> $failures
     */
    public function __construct(
        private Sequence $failures,
    ) {
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
        return new self($this->failures->map($map));
    }

    /**
     * @param Sequence<T>|self<T> $other
     *
     * @return self<T>
     */
    public function append(Sequence|self $other): self
    {
        if ($other instanceof self) {
            $other = $other->failures;
        }

        return new self(
            $this->failures->append($other),
        );
    }

    /**
     * @return Sequence<T>
     */
    public function unwrap(): Sequence
    {
        return $this->failures;
    }
}
