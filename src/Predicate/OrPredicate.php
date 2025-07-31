<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Predicate;

use Innmind\Immutable\Predicate;

/**
 * @psalm-immutable
 * @template A
 * @template B
 * @implements Predicate<A|B>
 */
final class OrPredicate implements Predicate
{
    /**
     * @param Predicate<A> $a
     * @param Predicate<B> $b
     */
    private function __construct(
        private Predicate $a,
        private Predicate $b,
    ) {
    }

    #[\Override]
    public function __invoke(mixed $value): bool
    {
        return ($this->a)($value) || ($this->b)($value);
    }

    /**
     * @psalm-pure
     * @template T
     * @template V
     *
     * @param Predicate<T> $a
     * @param Predicate<V> $b
     *
     * @return self<T, V>
     */
    #[\NoDiscard]
    public static function of(Predicate $a, Predicate $b): self
    {
        return new self($a, $b);
    }

    /**
     * @template C
     *
     * @param Predicate<C> $other
     *
     * @return self<A|B, C>
     */
    #[\NoDiscard]
    public function or(Predicate $other): self
    {
        return new self($this, $other);
    }

    /**
     * @template C
     *
     * @param Predicate<C> $other
     *
     * @return AndPredicate<A|B, C>
     */
    #[\NoDiscard]
    public function and(Predicate $other): AndPredicate
    {
        return AndPredicate::of($this, $other);
    }
}
