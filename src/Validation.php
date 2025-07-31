<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @template-covariant F
 * @template-covariant S
 * @psalm-immutable
 */
final class Validation
{
    private Validation\Implementation $implementation;

    private function __construct(Validation\Implementation $implementation)
    {
        $this->implementation = $implementation;
    }

    /**
     * @template A
     * @template B
     * @psalm-pure
     *
     * @param A $value
     *
     * @return self<B, A>
     */
    #[\NoDiscard]
    public static function success($value): self
    {
        return new self(Validation\Success::of($value));
    }

    /**
     * @template A
     * @template B
     * @psalm-pure
     *
     * @param A $value
     *
     * @return self<A, B>
     */
    #[\NoDiscard]
    public static function fail($value): self
    {
        return new self(Validation\Fail::of($value));
    }

    /**
     * @template T
     *
     * @param callable(S): T $map
     *
     * @return self<F, T>
     */
    #[\NoDiscard]
    public function map(callable $map): self
    {
        return new self($this->implementation->map($map));
    }

    /**
     * @template T
     * @template V
     *
     * @param callable(S): self<T, V> $map
     *
     * @return self<F|T, V>
     */
    #[\NoDiscard]
    public function flatMap(callable $map): self
    {
        return new self($this->implementation->flatMap(
            $map,
            static fn(self $self) => $self->implementation,
        ));
    }

    /**
     * @template T
     *
     * @param callable(F): T $map
     *
     * @return self<T, S>
     */
    #[\NoDiscard]
    public function mapFailures(callable $map): self
    {
        return new self($this->implementation->mapFailures($map));
    }

    /**
     * @template T
     * @template V
     *
     * @param callable(Sequence<F>): self<T, V> $map
     *
     * @return self<T, S|V>
     */
    #[\NoDiscard]
    public function otherwise(callable $map): self
    {
        return $this->implementation->otherwise($map);
    }

    /**
     * @template A
     * @template T
     *
     * @param self<F, A> $other
     * @param callable(S, A): T $fold
     *
     * @return self<F, T>
     */
    #[\NoDiscard]
    public function and(self $other, callable $fold): self
    {
        return new self($this->implementation->and(
            $other->implementation,
            $fold,
        ));
    }

    /**
     * @template T
     *
     * @param callable(S): T $success
     * @param callable(Sequence<F>): T $failure
     *
     * @return T
     */
    #[\NoDiscard]
    public function match(callable $success, callable $failure)
    {
        return $this->implementation->match($success, $failure);
    }

    /**
     * @return Maybe<S>
     */
    #[\NoDiscard]
    public function maybe(): Maybe
    {
        return $this->implementation->maybe();
    }

    /**
     * @return Either<Sequence<F>, S>
     */
    #[\NoDiscard]
    public function either(): Either
    {
        return $this->implementation->either();
    }
}
