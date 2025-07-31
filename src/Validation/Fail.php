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
 * @implements Implementation<F, S>
 * @psalm-immutable
 */
final class Fail implements Implementation
{
    /**
     * @param Sequence<F> $failures
     */
    private function __construct(
        private Sequence $failures,
    ) {
    }

    /**
     * @template A
     * @template B
     * @psalm-pure
     *
     * @param A $failure
     *
     * @return self<A, B>
     */
    public static function of($failure): self
    {
        return new self(Sequence::of($failure));
    }

    /**
     * @template T
     *
     * @param callable(S): T $map
     *
     * @return Implementation<F, T>
     */
    #[\Override]
    public function map(callable $map): Implementation
    {
        /** @var Implementation<F, T> */
        return $this;
    }

    /**
     * @template T
     * @template V
     *
     * @param callable(S): Validation<T, V> $map
     * @param pure-callable(Validation<T, V>): Implementation<T, V> $exfiltrate
     *
     * @return Implementation<F|T, V>
     */
    #[\Override]
    public function flatMap(callable $map, callable $exfiltrate): Implementation
    {
        /** @var Implementation<F|T, V> */
        return $this;
    }

    /**
     * @template T
     *
     * @param callable(F): T $map
     *
     * @return Implementation<T, S>
     */
    #[\Override]
    public function mapFailures(callable $map): Implementation
    {
        return new self($this->failures->map($map));
    }

    /**
     * @template T
     * @template V
     *
     * @param callable(Sequence<F>): Validation<T, V> $map
     *
     * @return Validation<T, S|V>
     */
    #[\Override]
    public function otherwise(callable $map): Validation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->failures);
    }

    /**
     * @template A
     * @template T
     *
     * @param Implementation<F, A> $other
     * @param callable(S, A): T $fold
     *
     * @return Implementation<F, T>
     */
    #[\Override]
    public function and(Implementation $other, callable $fold): Implementation
    {
        if ($other instanceof self) {
            /**
             * @psalm-suppress InvalidArgument
             * @var Implementation<F, T>
             */
            return new self($this->failures->append($other->failures));
        }

        /** @var Implementation<F, T> */
        return $this;
    }

    /**
     * @template T
     *
     * @param callable(S): T $success
     * @param callable(Sequence<F>): T $failure
     *
     * @return T
     */
    #[\Override]
    public function match(callable $success, callable $failure)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $failure($this->failures);
    }

    /**
     * @return Maybe<S>
     */
    #[\Override]
    public function maybe(): Maybe
    {
        return Maybe::nothing();
    }

    /**
     * @return Either<Sequence<F>, S>
     */
    #[\Override]
    public function either(): Either
    {
        return Either::left($this->failures);
    }
}
