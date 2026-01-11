<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence\Lookup;

use Innmind\Immutable\{
    Sequence\Implementation,
    Maybe,
    Attempt,
    Either,
};

/**
 * @psalm-immutable
 * @template T
 */
final class Last
{
    /**
     * @param Implementation<T> $implementation
     */
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @psalm-pure
     * @template A
     * @internal
     *
     * @param Implementation<A> $implementation
     *
     * @return self<A>
     */
    public static function of(Implementation $implementation): self
    {
        return new self($implementation);
    }

    /**
     * @template U
     *
     * @param callable(T): Maybe<U> $find
     *
     * @return Maybe<U>
     */
    public function maybe(callable $find): Maybe
    {
        /**
         * @psalm-suppress MixedArgument
         * @var Maybe<U>
         */
        return $this->implementation->reduce(
            Maybe::nothing(),
            static fn(Maybe $found, $value) => $find($value)->otherwise(
                static fn() => $found,
            ),
        );
    }

    /**
     * @template U
     *
     * @param callable(T): Attempt<U> $find
     *
     * @return Attempt<U>
     */
    public function attempt(\Throwable $default, callable $find): Attempt
    {
        /**
         * @psalm-suppress MixedArgument
         * @var Attempt<U>
         */
        return $this->implementation->reduce(
            Attempt::error($default),
            static fn(Attempt $found, $value) => $find($value)->recover(
                static fn($e) => $found->mapError(static fn() => $e),
            ),
        );
    }

    /**
     * @template U
     * @template L
     *
     * @param L $left
     * @param callable(T): Either<L, U> $find
     *
     * @return Either<L, U>
     */
    public function either(mixed $left, callable $find): Either
    {
        /**
         * @psalm-suppress MixedArgument
         * @var Either<L, U>
         */
        return $this->implementation->reduce(
            Either::left($left),
            static fn(Either $found, $value) => $find($value)->otherwise(
                static fn($left) => $found->leftMap(static fn(): mixed => $left),
            ),
        );
    }
}
