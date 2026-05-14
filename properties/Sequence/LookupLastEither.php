<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Sequence,
    Either,
};
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Sequence>
 */
final class LookupLastEither implements Property
{
    private function __construct(
        private Sequence $suffix,
        private mixed $a,
        private mixed $b,
        private mixed $left,
    ) {
    }

    public static function any(): Set
    {
        return Set::compose(
            static fn(...$args) => new self(...$args),
            Set::sequence(Set::type())->map(static fn($values) => Sequence::of(...$values)),
            Set::type(),
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
        $assert->same(
            $this->left,
            $systemUnderTest
                ->lookup()
                ->last()
                ->either(
                    $this->left,
                    fn() => Either::left($this->left),
                )
                ->match(
                    static fn() => null,
                    static fn($left) => $left,
                ),
        );
        $assert->same(
            $this->b,
            $systemUnderTest
                ->add($this->a)
                ->append($this->suffix->exclude(
                    fn($value) => $value === $this->a,
                ))
                ->lookup()
                ->last()
                ->either(
                    $this->left,
                    fn($value) => match ($value) {
                        $this->a => Either::right($this->b),
                        default => Either::left($this->left),
                    },
                )
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );

        return $systemUnderTest;
    }
}
