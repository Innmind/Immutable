<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Attempt;

use Innmind\Immutable\{
    Attempt,
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
     * @param callable(T): Attempt<U> $map
     *
     * @return Attempt<U>
     */
    public function flatMap(callable $map): Attempt;

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
     * @template U
     *
     * @param callable(\Throwable): Attempt<U> $recover
     *
     * @return Attempt<T|U>
     */
    public function recover(callable $recover): Attempt;

    /**
     * @return Maybe<T>
     */
    public function maybe(): Maybe;

    /**
     * @return Either<\Throwable, T>
     */
    public function either(): Either;

    /**
     * @return Attempt<T>
     */
    public function memoize(): Attempt;
}
