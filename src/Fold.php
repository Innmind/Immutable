<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Fold\{
    Implementation,
    With,
    Result,
    Failure,
};

/**
 * @template F Failure
 * @template R Result
 * @template C Computation
 * @psalm-immutable
 */
final class Fold
{
    private Implementation $fold;

    private function __construct(Implementation $fold)
    {
        $this->fold = $fold;
    }

    /**
     * @psalm-pure
     *
     * @template T
     * @template U
     * @template V
     *
     * @param V $value
     *
     * @return self<T, U, V>
     */
    public static function with(mixed $value): self
    {
        return new self(new With($value));
    }

    /**
     * @psalm-pure
     *
     * @template T
     * @template U
     * @template V
     *
     * @param U $result
     *
     * @return self<T, U, V>
     */
    public static function result(mixed $result): self
    {
        return new self(new Result($result));
    }

    /**
     * @psalm-pure
     *
     * @template T
     * @template U
     * @template V
     *
     * @param T $failure
     *
     * @return self<T, U, V>
     */
    public static function fail(mixed $failure): self
    {
        return new self(new Failure($failure));
    }

    /**
     * @template A
     *
     * @param callable(C): A $map
     *
     * @return self<F, R, A>
     */
    public function map(callable $map): self
    {
        return new self($this->fold->map($map));
    }

    /**
     * @template T
     * @template U
     * @template V
     *
     * @param callable(C): self<T, U, V> $map
     *
     * @return self<F|T, R|U, V>
     */
    public function flatMap(callable $map): self
    {
        return $this->fold->flatMap($map);
    }

    /**
     * @return Maybe<Either<F, R>>
     */
    public function maybe(): Maybe
    {
        return $this->fold->maybe();
    }

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
    ): mixed {
        return $this->fold->match($with, $result, $failure);
    }
}
