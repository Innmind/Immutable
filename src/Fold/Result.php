<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Fold;

use Innmind\Immutable\{
    Fold,
    Maybe,
    Either,
};

/**
 * @template F1
 * @template R1
 * @template C1
 * @implements Implementation<F1, R1, C1>
 * @psalm-immutable
 * @internal
 * @psalm-suppress DeprecatedClass
 */
final class Result implements Implementation
{
    /**
     * @param R1 $result
     */
    public function __construct(
        private mixed $result,
    ) {
    }

    /**
     * @template A
     *
     * @param callable(C1): A $map
     *
     * @return self<F1, R1, A>
     */
    #[\Override]
    public function map(callable $map): self
    {
        /** @var self<F1, R1, A> */
        return $this;
    }

    #[\Override]
    public function flatMap(callable $map): Fold
    {
        return Fold::result($this->result);
    }

    #[\Override]
    public function mapResult(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->result));
    }

    /**
     * @template A
     *
     * @param callable(F1): A $map
     *
     * @return self<A, R1, C1>
     */
    #[\Override]
    public function mapFailure(callable $map): self
    {
        /** @var self<A, R1, C1> */
        return $this;
    }

    /**
     * @return Maybe<Either<F1, R1>>
     */
    #[\Override]
    public function maybe(): Maybe
    {
        /** @var Maybe<Either<F1, R1>> */
        return Maybe::just(Either::right($this->result));
    }

    #[\Override]
    public function match(
        callable $with,
        callable $result,
        callable $failure,
    ): mixed {
        /** @psalm-suppress ImpureFunctionCall */
        return $result($this->result);
    }
}
