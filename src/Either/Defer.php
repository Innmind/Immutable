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
final class Defer implements Implementation
{
    /** @var callable(): Either<L1, R1> */
    private $deferred;
    /** @var ?Either<L1, R1> */
    private ?Either $value = null;

    /**
     * @param callable(): Either<L1, R1> $deferred
     */
    public function __construct($deferred)
    {
        $this->deferred = $deferred;
    }

    public function map(callable $map): self
    {
        return new self(fn() => $this->unwrap()->map($map));
    }

    public function flatMap(callable $map): Either
    {
        return Either::defer(fn() => $this->unwrap()->flatMap($map));
    }

    public function leftMap(callable $map): self
    {
        return new self(fn() => $this->unwrap()->leftMap($map));
    }

    public function match(callable $right, callable $left)
    {
        return $this->unwrap()->match($right, $left);
    }

    public function otherwise(callable $otherwise): Either
    {
        return Either::defer(fn() => $this->unwrap()->otherwise($otherwise));
    }

    public function filter(callable $predicate, callable $otherwise): Implementation
    {
        return new self(fn() => $this->unwrap()->filter($predicate, $otherwise));
    }

    public function maybe(): Maybe
    {
        return Maybe::defer(fn() => $this->unwrap()->maybe());
    }

    /**
     * @return Either<L1, R1>
     */
    public function memoize(): Either
    {
        return $this->unwrap();
    }

    public function flip(): self
    {
        return new self(fn() => $this->unwrap()->flip());
    }

    /**
     * @return Either<L1, R1>
     */
    private function unwrap(): Either
    {
        /**
         * @psalm-suppress InaccessibleProperty
         * @psalm-suppress ImpureFunctionCall
         */
        return $this->value ??= ($this->deferred)();
    }
}
