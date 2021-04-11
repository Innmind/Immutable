<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Either;

use Innmind\Immutable\Either;

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
     * @template B
     *
     * @param callable(R): Either<L, B> $map
     *
     * @return Either<L, B>
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
     * @param callable(L): T $left
     * @param callable(R): T $right
     *
     * @return T
     */
    public function match(callable $left, callable $right);

    /**
     * @param callable(): Either<L, R> $otherwise
     *
     * @return Either<L, R>
     */
    public function otherwise(callable $otherwise): Either;

    /**
     * @param callable(R): bool $predicate
     * @param callable(): L $otherwise
     *
     * @return self<L, R>
     */
    public function filter(callable $predicate, callable $otherwise): self;
}
