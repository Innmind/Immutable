<?php

declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template T
 */
final class Positionned
{
    /**
     * @param T $value
     */
    private function __construct(
        private mixed $value,
        private Position $position,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    #[\NoDiscard]
    public static function of(mixed $value): self
    {
        return new self($value, Position::only);
    }

    /**
     * @internal
     *
     * @return Sequence<self<T>>
     */
    #[\NoDiscard]
    public function and(self $next): Sequence
    {
        if ($this->position === Position::only) {
            return Sequence::of(
                new self($this->value, Position::first),
                new self($next->value, Position::last),
            );
        }

        // there could be other scenarii but this is not supported because it
        // means someone is trying to position values out of order.
        // For example $this is last and followed by another value then we can't
        // determine the position of $this, as it could be either first or
        // middle depending on user context.

        return Sequence::of(
            new self($this->value, Position::middle),
            new self($next->value, Position::last),
        );
    }

    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            $map($this->value),
            $this->position,
        );
    }

    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<T|U>
     */
    #[\NoDiscard]
    public function mapPosition(Position $position, callable $map): self
    {
        if ($this->position !== $position) {
            return $this;
        }

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            $map($this->value),
            $this->position,
        );
    }

    /**
     * @return T
     */
    #[\NoDiscard]
    public function unwrap(): mixed
    {
        return $this->value;
    }

    #[\NoDiscard]
    public function position(): Position
    {
        return $this->position;
    }

    #[\NoDiscard]
    public function first(): bool
    {
        return $this->position->first();
    }

    #[\NoDiscard]
    public function last(): bool
    {
        return $this->position->last();
    }
}
