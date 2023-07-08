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
final class Identity implements Property
{
    /** @var T */
    private mixed $value;
    /** @var callable(T, T): bool */
    private $equals;

    /**
     * @param T $value
     * @param callable(T, T): bool $equals
     */
    public function __construct(mixed $value, callable $equals)
    {
        $this->value = $value;
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
        return Set\Decorate::immutable(
            static fn($value) => new self($value, $equals),
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
            $monoid->identity(),
            $monoid->identity(),
        ));
        $assert->true(($this->equals)(
            $this->value,
            $monoid->combine($monoid->identity(), $this->value),
        ));
        $assert->true(($this->equals)(
            $this->value,
            $monoid->combine($this->value, $monoid->identity()),
        ));
        // make sure the identiy is not altered after using a concrete value
        $assert->true(($this->equals)(
            $monoid->identity(),
            $monoid->identity(),
        ));

        return $monoid;
    }
}
