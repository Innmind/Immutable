<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Monoid;

use Innmind\BlackBox\{
    Set,
    Property,
};
use PHPUnit\Framework\Assert;

/**
 * @template T
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

    /**
     * @template A
     *
     * @param Set<A> $values
     * @param callable(A, A): bool $equals
     *
     * @return Set<self<A>>
     */
    public static function any(Set $values, callable $equals): Set
    {
        return Set\Decorate::immutable(
            static fn($value) => new self($value, $equals),
            $values,
        );
    }

    public function name(): string
    {
        return 'Identity value has no effect on the combined value';
    }

    public function applicableTo(object $monoid): bool
    {
        return true;
    }

    public function ensureHeldBy(object $monoid): object
    {
        Assert::assertTrue(($this->equals)(
            $monoid->identity(),
            $monoid->identity(),
        ));
        Assert::assertTrue(($this->equals)(
            $this->value,
            $monoid->combine($monoid->identity(), $this->value),
        ));
        Assert::assertTrue(($this->equals)(
            $this->value,
            $monoid->combine($this->value, $monoid->identity()),
        ));
        // make sure the identiy is not altered after using a concrete value
        Assert::assertTrue(($this->equals)(
            $monoid->identity(),
            $monoid->identity(),
        ));

        return $monoid;
    }
}
