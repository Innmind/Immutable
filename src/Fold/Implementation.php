<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Fold;

use Innmind\Immutable\{
    Fold,
    Maybe,
    Either,
};

/**
 * @template F Failure
 * @template R Result
 * @template C Computation
 * @psalm-immutable
 * @internal
 */
interface Implementation
{
    /**
     * @template A
     *
     * @param callable(C): A $map
     *
     * @return self<F, R, A>
     */
    public function map(callable $map): self;

    /**
     * @template T
     * @template U
     * @template V
     *
     * @param callable(C): Fold<T, U, V> $map
     *
     * @return Fold<F|T, R|U, V>
     */
    public function flatMap(callable $map): Fold;

    /**
     * @return Maybe<Either<F, R>>
     */
    public function maybe(): Maybe;

    /**
     * @template T
     *
     * @param callable(C): T $with
     * @param callable(R): T $result
     * @param callable(F): T $failure
     *
     * @return T
     */
    public function match(
        callable $with,
        callable $result,
        callable $failure,
    ): mixed;
}
