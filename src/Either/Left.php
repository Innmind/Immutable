<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Either;

use Innmind\Immutable\{
    Either,
    Maybe,
};

/**
 * @template L1
 * @template R1
 * @implements Implementation<L1, R1>
 * @psalm-immutable
 * @internal
 */
final class Left implements Implementation
{
    /** @var L1 */
    private $value;

    /**
     * @param L1 $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @template T
     *
     * @param callable(R1): T $map
     *
     * @return self<L1, T>
     */
    #[\Override]
    public function map(callable $map): self
    {
        /** @var self<L1, T> */
        return $this;
    }

    #[\Override]
    public function flatMap(callable $map): Either
    {
        return Either::left($this->value);
    }

    #[\Override]
    public function leftMap(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    #[\Override]
    public function match(callable $right, callable $left)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $left($this->value);
    }

    #[\Override]
    public function otherwise(callable $otherwise): Either
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $otherwise($this->value);
    }

    #[\Override]
    public function filter(callable $predicate, callable $otherwise): self
    {
        return $this;
    }

    #[\Override]
    public function maybe(): Maybe
    {
        return Maybe::nothing();
    }

    /**
     * @return Either<L1, R1>
     */
    #[\Override]
    public function memoize(): Either
    {
        return Either::left($this->value);
    }

    #[\Override]
    public function flip(): Implementation
    {
        return new Right($this->value);
    }

    #[\Override]
    public function eitherWay(callable $right, callable $left): Either
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $left($this->value);
    }
}
