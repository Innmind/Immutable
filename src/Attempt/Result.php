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

    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    public function flatMap(callable $map): Attempt
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value);
    }

    public function match(callable $result, callable $error)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $result($this->value);
    }

    public function recover(callable $recover): Attempt
    {
        return Attempt::result($this->value);
    }

    public function maybe(): Maybe
    {
        return Maybe::just($this->value);
    }

    public function either(): Either
    {
        return Either::right($this->value);
    }

    /**
     * @return Attempt<R1>
     */
    public function memoize(): Attempt
    {
        return Attempt::result($this->value);
    }
}
