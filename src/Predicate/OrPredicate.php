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
    /** @var Predicate<A> */
    private Predicate $a;
    /** @var Predicate<B> */
    private Predicate $b;

    /**
     * @param Predicate<A> $a
     * @param Predicate<B> $b
     */
    private function __construct(Predicate $a, Predicate $b)
    {
        $this->a = $a;
        $this->b = $b;
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
    public function and(Predicate $other): AndPredicate
    {
        return AndPredicate::of($this, $other);
    }
}
