<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Monoid;

use Innmind\Immutable\Monoid;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @template T of Monoid
 * @implements Property<T>
 */
final class Associativity implements Property
{
    /** @var T */
    private mixed $a;
    /** @var T */
    private mixed $b;
    /** @var T */
    private mixed $c;
    /** @var callable(T, T): bool */
    private $equals;

    /**
     * @param T $a
     * @param T $b
     * @param T $c
     * @param callable(T, T): bool $equals
     */
    public function __construct(mixed $a, mixed $b, mixed $c, callable $equals)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
        $this->equals = $equals;
    }

    public static function any(): Set
    {
        throw new \LogicException('Use ::of() instead');
    }

    /**
     * @template A
     *
     * @param Set<A> $values
     * @param callable(A, A): bool $equals
     *
     * @return Set<self<A>>
     */
    public static function of(Set $values, callable $equals): Set
    {
        return Set\Composite::immutable(
            static fn($a, $b, $c) => new self($a, $b, $c, $equals),
            $values,
            $values,
            $values,
        );
    }

    public function applicableTo(object $monoid): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $monoid): object
    {
        $assert->true(($this->equals)(
            $monoid->combine($this->a, $monoid->combine($this->b, $this->c)),
            $monoid->combine($monoid->combine($this->a, $this->b), $this->c),
        ));

        return $monoid;
    }
}
