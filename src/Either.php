<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Either\{
    Implementation,
    Left,
    Right,
};

/**
 * @template L
 * @template R
 * @psalm-immutable
 */
final class Either
{
    /** @var Implementation<L, R> */
    private Implementation $either;

    /**
     * @param Implementation<L, R> $either
     */
    private function __construct(Implementation $either)
    {
        $this->either = $either;
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
    public static function left($value): self
    {
        return new self(new Left($value));
    }

    /**
     * @template A
     * @template B
     * @psalm-pure
     *
     * @param B $value
     *
     * @return self<A, B>
     */
    public static function right($value): self
    {
        return new self(new Right($value));
    }

    /**
     * @template T
     *
     * @param callable(R): T $map
     *
     * @return self<L, T>
     */
    public function map(callable $map): self
    {
        return new self($this->either->map($map));
    }

    /**
     * @template A
     * @template B
     *
     * @param callable(R): Either<A, B> $map
     *
     * @return Either<L|A, B>
     */
    public function flatMap(callable $map): self
    {
        return $this->either->flatMap($map);
    }

    /**
     * @template T
     *
     * @param callable(L): T $map
     *
     * @return self<T, R>
     */
    public function leftMap(callable $map): self
    {
        return new self($this->either->leftMap($map));
    }

    /**
     * @template T
     *
     * @param callable(R): T $right
     * @param callable(L): T $left
     *
     * @return T
     */
    public function match(callable $right, callable $left)
    {
        return $this->either->match($right, $left);
    }

    /**
     * @template A
     * @template B
     *
     * @param callable(L): Either<A, B> $otherwise
     *
     * @return Either<A, R|B>
     */
    public function otherwise(callable $otherwise): self
    {
        return $this->either->otherwise($otherwise);
    }

    /**
     * @template A
     *
     * @param callable(R): bool $predicate
     * @param callable(): A $otherwise
     *
     * @return self<L|A, R>
     */
    public function filter(callable $predicate, callable $otherwise): self
    {
        return new self($this->either->filter($predicate, $otherwise));
    }
}
