<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Maybe\{
    Implementation,
    Just,
    Nothing,
};

/**
 * @template T
 * @psalm-immutable
 */
final class Maybe
{
    /** @var Implementation<T> */
    private Implementation $maybe;

    /**
     * @param Implementation<T> $maybe
     */
    private function __construct(Implementation $maybe)
    {
        $this->maybe = $maybe;
    }

    /**
     * @template V
     * @psalm-pure
     *
     * @param V $value
     *
     * @return self<V>
     */
    public static function just($value): self
    {
        return new self(new Just($value));
    }

    /**
     * @psalm-pure
     */
    public static function nothing(): self
    {
        return new self(new Nothing);
    }

    /**
     * @template V
     * @psalm-pure
     *
     * @param V|null $value
     *
     * @return self<V>
     */
    public static function of($value): self
    {
        if (\is_null($value)) {
            return self::nothing();
        }

        return self::just($value);
    }

    /**
     * The comprehension is called only when all values exist
     *
     * @no-named-arguments
     */
    public static function all(self $first, self ...$rest): Maybe\Comprehension
    {
        return Maybe\Comprehension::of($first, ...$rest);
    }

    /**
     * @template V
     *
     * @param callable(T): V $map
     *
     * @return self<V>
     */
    public function map(callable $map): self
    {
        return new self($this->maybe->map($map));
    }

    /**
     * @template V
     *
     * @param callable(T): Maybe<V> $map
     *
     * @return Maybe<V>
     */
    public function flatMap(callable $map): self
    {
        return $this->maybe->flatMap($map);
    }

    /**
     * @template V
     *
     * @param callable(T): V $just
     * @param callable(): V $nothing
     *
     * @return V
     */
    public function match(callable $just, callable $nothing)
    {
        return $this->maybe->match($just, $nothing);
    }

    /**
     * @param callable(): Maybe<T> $otherwise
     *
     * @return Maybe<T>
     */
    public function otherwise(callable $otherwise): self
    {
        return $this->maybe->otherwise($otherwise);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self
    {
        return new self($this->maybe->filter($predicate));
    }
}
