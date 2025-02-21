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

    #[\Override]
    public function map(callable $map): self
    {
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return new self(static fn() => $map($value()));
    }

    #[\Override]
    public function flatMap(callable $map): Identity
    {
        $value = $this->value;

        /** @psalm-suppress ImpureFunctionCall */
        return Identity::lazy(static fn() => $map($value())->unwrap());
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        $value = $this->value;

        return Sequence::lazy(static fn() => yield $value());
    }

    #[\Override]
    public function unwrap(): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->value)();
    }
}
