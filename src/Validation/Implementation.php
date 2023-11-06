<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Validation;

use Innmind\Immutable\{
    Validation,
    Maybe,
    Either,
    Sequence,
};

/**
 * @template-covariant F
 * @template-covariant S
 * @psalm-immutable
 */
interface Implementation
{
    /**
     * @template T
     *
     * @param callable(S): T $map
     *
     * @return self<F, T>
     */
    public function map(callable $map): self;

    /**
     * @template T
     * @template V
     *
     * @param callable(S): Validation<T, V> $map
     * @param pure-callable(Validation<T, V>): self<T, V> $exfiltrate
     *
     * @return self<F|T, V>
     */
    public function flatMap(callable $map, callable $exfiltrate): self;

    /**
     * @template T
     *
     * @param callable(F): T $map
     *
     * @return self<T, S>
     */
    public function mapFailures(callable $map): self;

    /**
     * @template T
     * @template V
     *
     * @param callable(Sequence<F>): Validation<T, V> $map
     *
     * @return Validation<T, S|V>
     */
    public function otherwise(callable $map): Validation;

    /**
     * @template A
     * @template T
     *
     * @param self<F, A> $other
     * @param callable(S, A): T $fold
     *
     * @return self<F, T>
     */
    public function and(self $other, callable $fold): self;

    /**
     * @template T
     *
     * @param callable(S): T $success
     * @param callable(Sequence<F>): T $failure
     *
     * @return T
     */
    public function match(callable $success, callable $failure);

    /**
     * @return Maybe<S>
     */
    public function maybe(): Maybe;

    /**
     * @return Either<Sequence<F>, S>
     */
    public function either(): Either;
}
