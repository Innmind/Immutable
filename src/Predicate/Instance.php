<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Predicate;

use Innmind\Immutable\Predicate;

/**
 * @psalm-immutable
 * @template A of object
 * @implements Predicate<A>
 */
final class Instance implements Predicate
{
    /**
     * @param class-string<A> $class
     */
    private function __construct(
        private string $class,
    ) {
    }

    #[\Override]
    public function __invoke(mixed $value): bool
    {
        return $value instanceof $this->class;
    }

    /**
     * @psalm-pure
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return self<T>
     */
    #[\NoDiscard]
    public static function of(string $class): self
    {
        return new self($class);
    }

    /**
     * @template T
     *
     * @param Predicate<T> $predicate
     *
     * @return OrPredicate<A, T>
     */
    #[\NoDiscard]
    public function or(Predicate $predicate): OrPredicate
    {
        return OrPredicate::of($this, $predicate);
    }

    /**
     * @template T
     *
     * @param Predicate<T> $predicate
     *
     * @return AndPredicate<A, T>
     */
    #[\NoDiscard]
    public function and(Predicate $predicate): AndPredicate
    {
        return AndPredicate::of($this, $predicate);
    }
}
