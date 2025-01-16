<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Maybe,
    Either,
};

/**
 * @template-covariant T
 * @template-covariant C
 * @psalm-immutable
 */
final class Sink
{
    /**
     * @param Implementation<T> $implementation
     * @param C $carry
     */
    private function __construct(
        private Implementation $implementation,
        private mixed $carry,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param Implementation<A> $implementation
     * @param B $carry
     *
     * @return self<A, B>
     */
    public static function of(Implementation $implementation, mixed $carry): self
    {
        return new self($implementation, $carry);
    }

    /**
     * @param callable(C, T, Sink\Continuation<C>): Sink\Continuation<C> $reducer
     *
     * @return C
     */
    public function until(callable $reducer): mixed
    {
        return $this->implementation->sink(
            $this->carry,
            $reducer,
        );
    }

    /**
     * This will consume all the values from the Sequence as long as a value is
     * contained in the returned Maybe.
     *
     * @param callable(C, T): Maybe<C> $reducer
     *
     * @return Maybe<C>
     */
    public function maybe(callable $reducer): Maybe
    {
        return $this->implementation->sink(
            Maybe::just($this->carry),
            static function($carry, $value, $continuation) use ($reducer) {
                /**
                 * @var Maybe<C> $carry
                 * @var T $value
                 */

                /** @psalm-suppress MixedArgument */
                $maybe = $carry
                    ->flatMap(static fn($carry) => $reducer($carry, $value))
                    ->memoize();

                return $maybe->match(
                    static fn() => $continuation->continue($maybe),
                    static fn() => $continuation->stop($maybe),
                );
            },
        );
    }

    /**
     * This will consume all the values from the Sequence as long as a right
     * value is contained in the returned Either.
     *
     * @template E
     *
     * @param callable(C, T): Either<E, C> $reducer
     *
     * @return Either<E, C>
     */
    public function either(callable $reducer): Either
    {
        /** @var Either<E, C>  */
        $carry = Either::right($this->carry);

        return $this->implementation->sink(
            $carry,
            static function($carry, $value, $continuation) use ($reducer) {
                /**
                 * @var Either<E, C> $carry
                 * @var T $value
                 */

                /** @psalm-suppress MixedArgument */
                $either = $carry
                    ->flatMap(static fn($carry) => $reducer($carry, $value))
                    ->memoize();

                return $either->match(
                    static fn() => $continuation->continue($either),
                    static fn() => $continuation->stop($either),
                );
            },
        );
    }
}
