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
final class Success implements Implementation
{
    /** @var S */
    private $value;

    /**
     * @param S $value
     */
    private function __construct($value)
    {
        $this->value = $value;
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
    public static function of($value): self
    {
        return new self($value);
    }

    /**
     * @template T
     *
     * @param callable(S): T $map
     *
     * @return Implementation<F, T>
     */
    public function map(callable $map): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
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
    public function flatMap(callable $map, callable $exfiltrate): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $exfiltrate($map($this->value));
    }

    /**
     * @template T
     *
     * @param callable(F): T $map
     *
     * @return Implementation<T, S>
     */
    public function mapFailures(callable $map): Implementation
    {
        /** @var Implementation<T, S> */
        return $this;
    }

    /**
     * @template T
     * @template V
     *
     * @param callable(Sequence<F>): Validation<T, V> $map
     *
     * @return Validation<T, S|V>
     */
    public function otherwise(callable $map): Validation
    {
        return Validation::success($this->value);
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
    public function and(Implementation $other, callable $fold): Implementation
    {
        if ($other instanceof self) {
            /** @psalm-suppress ImpureFunctionCall */
            return new self($fold($this->value, $other->value));
        }

        /** @var Implementation<F, T> */
        return $other;
    }

    /**
     * @template T
     *
     * @param callable(S): T $success
     * @param callable(Sequence<F>): T $failure
     *
     * @return T
     */
    public function match(callable $success, callable $failure)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $success($this->value);
    }

    /**
     * @return Maybe<S>
     */
    public function maybe(): Maybe
    {
        return Maybe::just($this->value);
    }

    /**
     * @return Either<Sequence<F>, S>
     */
    public function either(): Either
    {
        return Either::right($this->value);
    }
}
