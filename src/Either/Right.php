<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Either;

use Innmind\Immutable\{
    Either,
    Maybe,
    Attempt,
};

/**
 * @template L1
 * @template R1
 * @implements Implementation<L1, R1>
 * @psalm-immutable
 * @internal
 */
final class Right implements Implementation
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
    public function flatMap(callable $map): Either
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value);
    }

    /**
     * @template T
     *
     * @param callable(L1): T $map
     *
     * @return self<T, R1>
     */
    #[\Override]
    public function leftMap(callable $map): self
    {
        /** @var self<T, R1> */
        return $this;
    }

    #[\Override]
    public function match(callable $right, callable $left)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $right($this->value);
    }

    #[\Override]
    public function otherwise(callable $otherwise): Either
    {
        return Either::right($this->value);
    }

    #[\Override]
    public function filter(callable $predicate, callable $otherwise): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        if ($predicate($this->value) === true) {
            return $this;
        }

        /** @psalm-suppress ImpureFunctionCall */
        return new Left($otherwise());
    }

    #[\Override]
    public function maybe(): Maybe
    {
        return Maybe::just($this->value);
    }

    #[\Override]
    public function attempt(callable $error): Attempt
    {
        return Attempt::result($this->value);
    }

    /**
     * @return Either<L1, R1>
     */
    #[\Override]
    public function memoize(): Either
    {
        return Either::right($this->value);
    }

    #[\Override]
    public function flip(): Implementation
    {
        return new Left($this->value);
    }

    #[\Override]
    public function eitherWay(callable $right, callable $left): Either
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $right($this->value);
    }
}
