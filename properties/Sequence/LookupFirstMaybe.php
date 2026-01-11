<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Sequence,
    Maybe,
};
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Sequence>
 */
final class LookupFirstMaybe implements Property
{
    private function __construct(
        private Sequence $prefix,
        private mixed $a,
        private mixed $b,
    ) {
    }

    public static function any(): Set\Provider
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::sequence(Set::type())->map(static fn($values) => Sequence::of(...$values)),
            Set::type(),
            Set::type(),
        );
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $assert->null(
            $systemUnderTest
                ->lookup()
                ->first()
                ->maybe(Maybe::nothing(...))
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );
        $assert->same(
            $this->b,
            $systemUnderTest
                ->prepend($this->prefix->add($this->a))
                ->lookup()
                ->first()
                ->maybe(fn($value) => match ($value) {
                    $this->a => Maybe::just($this->b),
                    default => Maybe::nothing(),
                })
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );

        return $systemUnderTest;
    }
}
