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
    public function map(callable $map): self
    {
        /** @var self<T> */
        return $this;
    }

    public function flatMap(callable $map): Attempt
    {
        return Attempt::error($this->value);
    }

    public function match(callable $result, callable $error)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $error($this->value);
    }

    public function recover(callable $recover): Attempt
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $recover($this->value);
    }

    public function maybe(): Maybe
    {
        return Maybe::nothing();
    }

    public function either(): Either
    {
        return Either::left($this->value);
    }

    /**
     * @return Attempt<R1>
     */
    public function memoize(): Attempt
    {
        return Attempt::error($this->value);
    }
}
