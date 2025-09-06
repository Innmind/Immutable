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
     * @param pure-callable(Attempt<U>): self<U> $exfiltrate
     *
     * @return self<U>
     */
    public function flatMap(
        callable $map,
        callable $exfiltrate,
    ): self;

    /**
     * @template U
     *
     * @param callable(T): Attempt<U> $map
     * @param pure-callable(Attempt<U>): self<U> $exfiltrate
     *
     * @return self<U>
     */
    public function guard(
        callable $map,
        callable $exfiltrate,
    ): self;

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
     * @param callable(\Throwable): Attempt<U> $recover
     * @param pure-callable(Attempt<U>): self<U> $exfiltrate
     *
     * @return self<T|U>
     */
    public function recover(
        callable $recover,
        callable $exfiltrate,
    ): self;

    /**
     * @template U
     *
     * @param callable(\Throwable): Attempt<U> $recover
     * @param pure-callable(Attempt<U>): self<U> $exfiltrate
     *
     * @return self<T|U>
     */
    public function xrecover(
        callable $recover,
        callable $exfiltrate,
    ): self;

    /**
     * @return Maybe<T>
     */
    public function maybe(): Maybe;

    /**
     * @return Either<\Throwable, T>
     */
    public function either(): Either;

    /**
     * @param pure-callable(Attempt<T>): self<T> $exfiltrate
     *
     * @return self<T>
     */
    public function memoize(callable $exfiltrate): self;

    /**
     * @template V
     *
     * @param callable(T): Attempt<V> $result
     * @param callable(\Throwable): Attempt<V> $error
     * @param pure-callable(Attempt<V>): self<V> $exfiltrate
     *
     * @return self<V>
     */
    public function eitherWay(
        callable $result,
        callable $error,
        callable $exfiltrate,
    ): self;
}
