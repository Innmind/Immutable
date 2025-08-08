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
final class Error implements Implementation
{
    public function __construct(
        private \Throwable $value,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(R1): T $map
     *
     * @return self<T>
     */
    #[\Override]
    public function map(callable $map): self
    {
        /** @var self<T> */
        return $this;
    }

    #[\Override]
    public function flatMap(callable $map): Attempt
    {
        return Attempt::error($this->value);
    }

    #[\Override]
    public function match(callable $result, callable $error)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $error($this->value);
    }

    #[\Override]
    public function mapError(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    #[\Override]
    public function recover(callable $recover): Attempt
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $recover($this->value);
    }

    #[\Override]
    public function maybe(): Maybe
    {
        return Maybe::nothing();
    }

    #[\Override]
    public function either(): Either
    {
        return Either::left($this->value);
    }

    /**
     * @return Attempt<R1>
     */
    #[\Override]
    public function memoize(): Attempt
    {
        return Attempt::error($this->value);
    }

    #[\Override]
    public function eitherWay(callable $result, callable $error): Attempt
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $error($this->value);
    }
}
