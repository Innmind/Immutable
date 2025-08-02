<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Identity;

use Innmind\Immutable\Identity;
use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template T
 * @implements Implementation<T>
 */
final class InMemory implements Implementation
{
    /**
     * @param T $value
     */
    public function __construct(
        private mixed $value,
    ) {
    }

    #[\Override]
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    #[\Override]
    public function flatMap(callable $map): Identity
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value);
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        return Sequence::of($this->value);
    }

    #[\Override]
    public function unwrap(): mixed
    {
        return $this->value;
    }
}
