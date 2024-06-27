<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Identity;

use Innmind\Immutable\{
    Identity,
    Sequence,
};

/**
 * @psalm-immutable
 * @template T
 * @implements Implementation<T>
 */
final class Lazy implements Implementation
{
    /** @var callable(): T */
    private $value;

    /**
     * @param callable(): T $value
     */
    public function __construct(callable $value)
    {
        $this->value = $value;
    }

    public function map(callable $map): self
    {
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return new self(static fn() => $map($value()));
    }

    public function flatMap(callable $map): Identity
    {
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return Identity::lazy(static fn() => $map($value())->unwrap());
    }

    public function toSequence(): Sequence
    {
        return Sequence::lazy(fn() => yield $this->unwrap());
    }

    public function unwrap(): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->value)();
    }
}
