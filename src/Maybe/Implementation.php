<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\Maybe;

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
     * @param callable(): Maybe<T> $otherwise
     *
     * @return Maybe<T>
     */
    public function otherwise(callable $otherwise): Maybe;

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    public function filter(callable $predicate): self;
}
