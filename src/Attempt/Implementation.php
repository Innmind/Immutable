<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Attempt;

use Innmind\Immutable\{
    Maybe,
    Either,
};

/**
 * @template T
 * @psalm-immutable
 * @internal
 */
interface Implementation
{
    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    public function map(callable $map): self;

    /**
     * @template U
     *
     * @param callable(T): self<U> $map
     *
     * @return self<U>
     */
    public function flatMap(callable $map): self;

    /**
     * @template U
     *
     * @param callable(T): self<U> $map
     *
     * @return self<U>
     */
    public function guard(callable $map): self;

    /**
     * @return self<T>
     */
    public function guardError(): self;

    /**
     * @template U
     *
     * @param callable(T): U $result
     * @param callable(\Throwable): U $error
     *
     * @return U
     */
    public function match(callable $result, callable $error);

    /**
     * @param callable(\Throwable): \Throwable $map
     *
     * @return self<T>
     */
    public function mapError(callable $map): self;

    /**
     * @template U
     *
     * @param callable(\Throwable): self<U> $recover
     *
     * @return self<T|U>
     */
    public function recover(callable $recover): self;

    /**
     * @template U
     *
     * @param callable(\Throwable): self<U> $recover
     *
     * @return self<T|U>
     */
    public function xrecover(callable $recover): self;

    /**
     * @return Maybe<T>
     */
    public function maybe(): Maybe;

    /**
     * @return Either<\Throwable, T>
     */
    public function either(): Either;

    /**
     * @return self<T>
     */
    public function memoize(): self;

    /**
     * @template V
     *
     * @param callable(T): self<V> $result
     * @param callable(\Throwable): self<V> $error
     *
     * @return self<V>
     */
    public function eitherWay(
        callable $result,
        callable $error,
    ): self;
}
