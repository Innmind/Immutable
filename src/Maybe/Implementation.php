<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\{
    Maybe,
    Either,
    Sequence,
};

/**
 * @template T
 * @psalm-immutable
 * @internal
 */
interface Implementation
{
    /**
     * @template V
     *
     * @param callable(T): V $map
     *
     * @return self<V>
     */
    public function map(callable $map): self;

    /**
     * @template V
     *
     * @param callable(T): Maybe<V> $map
     *
     * @return Maybe<V>
     */
    public function flatMap(callable $map): Maybe;

    /**
     * @template V
     *
     * @param callable(T): V $just
     * @param callable(): V $nothing
     *
     * @return V
     */
    public function match(callable $just, callable $nothing);

    /**
     * @template V
     *
     * @param callable(): Maybe<V> $otherwise
     *
     * @return Maybe<T|V>
     */
    public function otherwise(callable $otherwise): Maybe;

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self;

    /**
     * @return Either<null, T>
     */
    public function either(): Either;

    /**
     * @return Maybe<T>
     */
    public function memoize(): Maybe;

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence;

    /**
     * @template V
     *
     * @param callable(T): Maybe<V> $just
     * @param callable(): Maybe<V> $nothing
     *
     * @return Maybe<V>
     */
    public function eitherWay(callable $just, callable $nothing): Maybe;
}
