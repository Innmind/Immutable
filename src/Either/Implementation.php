<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Either;

use Innmind\Immutable\{
    Either,
    Maybe,
    Attempt,
};

/**
 * @template L
 * @template R
 * @psalm-immutable
 * @internal
 */
interface Implementation
{
    /**
     * @template T
     *
     * @param callable(R): T $map
     *
     * @return self<L, T>
     */
    public function map(callable $map): self;

    /**
     * @template A
     * @template B
     *
     * @param callable(R): Either<A, B> $map
     *
     * @return Either<L|A, B>
     */
    public function flatMap(callable $map): Either;

    /**
     * @template T
     *
     * @param callable(L): T $map
     *
     * @return self<T, R>
     */
    public function leftMap(callable $map): self;

    /**
     * @template T
     *
     * @param callable(R): T $right
     * @param callable(L): T $left
     *
     * @return T
     */
    public function match(callable $right, callable $left);

    /**
     * @template A
     * @template B
     *
     * @param callable(L): Either<A, B> $otherwise
     *
     * @return Either<A, R|B>
     */
    public function otherwise(callable $otherwise): Either;

    /**
     * @template A
     *
     * @param callable(R): bool $predicate
     * @param callable(): A $otherwise
     *
     * @return self<L|A, R>
     */
    public function filter(callable $predicate, callable $otherwise): self;

    /**
     * @return Maybe<R>
     */
    public function maybe(): Maybe;

    /**
     * @param callable(L): \Throwable $error
     *
     * @return Attempt<R>
     */
    public function attempt(callable $error): Attempt;

    /**
     * @return Either<L, R>
     */
    public function memoize(): Either;

    /**
     * @return self<R, L>
     */
    public function flip(): self;

    /**
     * @template A
     * @template B
     *
     * @param callable(R): Either<A, B> $right
     * @param callable(L): Either<A, B> $left
     *
     * @return Either<A, B>
     */
    public function eitherWay(callable $right, callable $left): Either;
}
