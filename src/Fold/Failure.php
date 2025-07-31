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
final class Failure implements Implementation
{
    /**
     * @param F1 $failure
     */
    public function __construct(
        private mixed $failure,
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
        return Fold::fail($this->failure);
    }

    /**
     * @template A
     *
     * @param callable(R1): A $map
     *
     * @return self<F1, A, C1>
     */
    #[\Override]
    public function mapResult(callable $map): self
    {
        /** @var self<F1, A, C1> */
        return $this;
    }

    #[\Override]
    public function mapFailure(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->failure));
    }

    /**
     * @return Maybe<Either<F1, R1>>
     */
    #[\Override]
    public function maybe(): Maybe
    {
        /** @var Maybe<Either<F1, R1>> */
        return Maybe::just(Either::left($this->failure));
    }

    #[\Override]
    public function match(
        callable $with,
        callable $result,
        callable $failure,
    ): mixed {
        /** @psalm-suppress ImpureFunctionCall */
        return $failure($this->failure);
    }
}
