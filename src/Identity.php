<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @psalm-immutable
 * @template T
 */
final class Identity
{
    /** @var T */
    private mixed $value;

    /**
     * @param T $value
     */
    private function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-pure
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    public static function of(mixed $value): self
    {
        return new self($value);
    }

    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    /**
     * @template U
     *
     * @param callable(T): self<U> $map
     *
     * @return self<U>
     */
    public function flatMap(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value);
    }

    /**
     * @return T
     */
    public function unwrap(): mixed
    {
        return $this->value;
    }
}
