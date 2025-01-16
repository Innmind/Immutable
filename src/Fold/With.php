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
final class With implements Implementation
{
    /** @var C1 */
    private mixed $with;

    /**
     * @param C1 $with
     */
    public function __construct(mixed $with)
    {
        $this->with = $with;
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
        /**
         * @psalm-suppress ImpureFunctionCall
         * @var self<F1, R1, A>
         */
        return new self($map($this->with));
    }

    public function flatMap(callable $map): Fold
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->with);
    }

    /**
     * @template A
     *
     * @param callable(R1): A $map
     *
     * @return self<F1, A, C1>
     */
    public function mapResult(callable $map): self
    {
        /** @var self<F1, A, C1> */
        return $this;
    }

    /**
     * @template A
     *
     * @param callable(F1): A $map
     *
     * @return self<A, R1, C1>
     */
    public function mapFailure(callable $map): self
    {
        /** @var self<A, R1, C1> */
        return $this;
    }

    /**
     * @return Maybe<Either<F1, R1>>
     */
    public function maybe(): Maybe
    {
        /** @var Maybe<Either<F1, R1>> */
        return Maybe::nothing();
    }

    public function match(
        callable $with,
        callable $result,
        callable $failure,
    ): mixed {
        /** @psalm-suppress ImpureFunctionCall */
        return $with($this->with);
    }
}
