<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Attempt;

use Innmind\Immutable\{
    Attempt,
    Maybe,
    Either,
};

/**
 * @template R1
 * @implements Implementation<R1>
 * @psalm-immutable
 * @internal
 */
final class Result implements Implementation
{
    /**
     * @param R1 $value
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
    public function flatMap(callable $map): Attempt
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value);
    }

    #[\Override]
    public function match(callable $result, callable $error)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $result($this->value);
    }

    #[\Override]
    public function mapError(callable $map): self
    {
        return $this;
    }

    #[\Override]
    public function recover(callable $recover): Attempt
    {
        return Attempt::result($this->value);
    }

    #[\Override]
    public function maybe(): Maybe
    {
        return Maybe::just($this->value);
    }

    #[\Override]
    public function either(): Either
    {
        return Either::right($this->value);
    }

    /**
     * @return Attempt<R1>
     */
    #[\Override]
    public function memoize(): Attempt
    {
        return Attempt::result($this->value);
    }

    #[\Override]
    public function eitherWay(callable $result, callable $error): Attempt
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $result($this->value);
    }
}
