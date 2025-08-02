<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Sequence;

use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Sequence>
 */
final class Windows implements Property
{
    private function __construct(
        private int $size,
    ) {
    }

    public static function any(): Set
    {
        // Upper bound is 100 to avoid having too large windows as it would
        // reduce the probability to create multiple windows.
        return Set::integers()
            ->between(1, 100)
            ->map(static fn($size) => new self($size));
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $systemUnderTest
            ->windows($this->size)
            ->foreach(
                fn($window) => $assert
                    ->number($window->size())
                    ->int()
                    ->lessThanOrEqual($this->size),
            );

        if ($systemUnderTest->size() >= $this->size) {
            $systemUnderTest
                ->windows($this->size)
                ->foreach(fn($window) => $assert->same(
                    $this->size,
                    $window->size(),
                ));
        }

        $end = new \stdClass;
        $assert->same(
            $systemUnderTest->toList(),
            $systemUnderTest
                ->add($end)
                ->windows($this->size)
                ->flatMap(static fn($window) => match ($window->contains($end)) {
                    true => $window->dropEnd(1),
                    false => $window->take(1),
                })
                ->exclude(static fn($value) => $value === $end)
                ->toList(),
        );

        return $systemUnderTest;
    }
}
