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
 */
final class Result implements Implementation
{
    /** @var R1 */
    private mixed $result;

    /**
     * @param R1 $result
     */
    public function __construct(mixed $result)
    {
        $this->result = $result;
    }

    /**
     * @template A
     *
     * @param callable(C1): A $map
     *
     * @return self<F1, R1, A>
     */
    public function map(callable $map): self
    {
        /** @var self<F1, R1, A> */
        return $this;
    }

    public function flatMap(callable $map): Fold
    {
        return Fold::result($this->result);
    }

    /**
     * @return Maybe<Either<F1, R1>>
     */
    public function maybe(): Maybe
    {
        /** @var Maybe<Either<F1, R1>> */
        return Maybe::just(Either::right($this->result));
    }

    public function match(
        callable $with,
        callable $result,
        callable $failure,
    ): mixed {
        /** @psalm-suppress ImpureFunctionCall */
        return $result($this->result);
    }
}
