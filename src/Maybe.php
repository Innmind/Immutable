<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Maybe\{
    Implementation,
    Just,
    Nothing,
    Defer,
};

/**
 * @template-covariant T
 * @psalm-immutable
 */
final class Maybe
{
    /**
     * @param Implementation<T> $maybe
     */
    private function __construct(
        private Implementation $maybe,
    ) {
    }

    /**
     * @template V
     * @psalm-pure
     *
     * @param V $value
     *
     * @return self<V>
     */
    #[\NoDiscard]
    public static function just($value): self
    {
        return new self(new Just($value));
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
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
    #[\NoDiscard]
    public static function of($value): self
    {
        if (\is_null($value)) {
            return self::nothing();
        }

        return self::just($value);
    }

    /**
     * This method is to be used for IO operations
     *
     * @template V
     * @psalm-pure
     *
     * @param callable(): self<V> $deferred
     *
     * @return self<V>
     */
    #[\NoDiscard]
    public static function defer(callable $deferred): self
    {
        return new self(new Defer($deferred));
    }

    /**
     * The comprehension is called only when all values exist
     *
     * @psalm-pure
     * @no-named-arguments
     */
    #[\NoDiscard]
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
    #[\NoDiscard]
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
    #[\NoDiscard]
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
    #[\NoDiscard]
    public function match(callable $just, callable $nothing)
    {
        return $this->maybe->match($just, $nothing);
    }

    /**
     * @template V
     *
     * @param callable(): Maybe<V> $otherwise
     *
     * @return Maybe<T|V>
     */
    #[\NoDiscard]
    public function otherwise(callable $otherwise): self
    {
        return $this->maybe->otherwise($otherwise);
    }

    /**
     * This is the same behaviour as `filter` but it allows Psalm to understand
     * the type of the values contained in the returned Maybe
     *
     * @template S
     *
     * @param Predicate<S> $predicate
     *
     * @return self<S>
     */
    #[\NoDiscard]
    public function keep(Predicate $predicate): self
    {
        /** @var self<S> */
        return $this->filter($predicate);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    #[\NoDiscard]
    public function filter(callable $predicate): self
    {
        return new self($this->maybe->filter($predicate));
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    #[\NoDiscard]
    public function exclude(callable $predicate): self
    {
        /** @psalm-suppress MixedArgument */
        return $this->filter(static fn($value) => !$predicate($value));
    }

    /**
     * @return Either<null, T>
     */
    #[\NoDiscard]
    public function either(): Either
    {
        return $this->maybe->either();
    }

    /**
     * @param callable(): \Throwable $error
     *
     * @return Attempt<T>
     */
    #[\NoDiscard]
    public function attempt(callable $error): Attempt
    {
        return $this->maybe->attempt($error);
    }

    /**
     * Force loading the value in memory (only useful for a deferred Maybe)
     *
     * @return self<T>
     */
    #[\NoDiscard]
    public function memoize(): self
    {
        return $this->maybe->memoize();
    }

    /**
     * @return Sequence<T>
     */
    #[\NoDiscard]
    public function toSequence(): Sequence
    {
        return $this->maybe->toSequence();
    }

    /**
     * @template V
     *
     * @param callable(T): self<V> $just
     * @param callable(): self<V> $nothing
     *
     * @return self<V>
     */
    #[\NoDiscard]
    public function eitherWay(callable $just, callable $nothing): self
    {
        return $this->maybe->eitherWay($just, $nothing);
    }
}
