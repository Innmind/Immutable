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
final class First
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
        return $this->implementation->sink(
            Maybe::nothing(),
            static function($_, $value, $continuation) use ($find) {
                $found = $find($value);

                return $found->match(
                    static fn() => $continuation->stop($found),
                    static fn() => $continuation->continue($_),
                );
            },
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
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Attempt<U>
         */
        return $this->implementation->sink(
            Attempt::error($default),
            static function($_, $value, $continuation) use ($find) {
                $found = $find($value);

                return $found->match(
                    static fn() => $continuation->stop($found),
                    static fn() => $continuation->continue($_),
                );
            },
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
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Either<L, U>
         */
        return $this->implementation->sink(
            Either::left($left),
            static function($_, $value, $continuation) use ($find) {
                $found = $find($value);

                return $found->match(
                    static fn() => $continuation->stop($found),
                    static fn() => $continuation->continue($_),
                );
            },
        );
    }
}
