<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Sequence,
    Attempt,
};
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Sequence>
 */
final class LookupFirstAttempt implements Property
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
        $default = new \Exception;

        $assert->same(
            $default,
            $systemUnderTest
                ->lookup()
                ->first()
                ->attempt(
                    $default,
                    static fn() => Attempt::error($default),
                )
                ->match(
                    static fn() => null,
                    static fn($error) => $error,
                ),
        );
        $assert->same(
            $this->b,
            $systemUnderTest
                ->prepend($this->prefix->add($this->a))
                ->lookup()
                ->first()
                ->attempt(
                    $default,
                    fn($value) => match ($value) {
                        $this->a => Attempt::result($this->b),
                        default => Attempt::error($default),
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
