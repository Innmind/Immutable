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
    /** @var T */
    private mixed $value;

    /**
     * @param T $value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    public function flatMap(callable $map): Identity
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value);
    }

    public function toSequence(): Sequence
    {
        return Sequence::of($this->value);
    }

    public function unwrap(): mixed
    {
        return $this->value;
    }
}
