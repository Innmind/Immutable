<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Predicate;

use Innmind\Immutable\Predicate;

/**
 * @psalm-immutable
 * @template A
 * @template B
 * @implements Predicate<A&B>
 */
final class AndPredicate implements Predicate
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
        return ($this->a)($value) && ($this->b)($value);
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
     * @return OrPredicate<A&B, C>
     */
    public function or(Predicate $other): OrPredicate
    {
        /**
         * For some reason if using directly $this below Psalm loses the B type
         * @var Predicate<A&B>
         */
        $self = $this;

        return OrPredicate::of($self, $other);
    }

    /**
     * @template C
     *
     * @param Predicate<C> $other
     *
     * @return self<A&B, C>
     */
    public function and(Predicate $other): self
    {
        /**
         * For some reason if using directly $this below Psalm loses the B type
         * @var Predicate<A&B>
         */
        $self = $this;

        return new self($self, $other);
    }
}
