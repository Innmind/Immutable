<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Str;

use Innmind\Immutable\Str;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Str>
 */
final class DropEnd implements Property
{
    private function __construct(
        private int $size,
    ) {
    }

    public static function any(): Set
    {
        // Upper bound is 100 to avoid having too much a probability that the
        // resulting Str will be the same one as the input one.
        return Set::integers()
            ->between(0, 100)
            ->map(static fn($size) => new self($size));
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $result = $systemUnderTest->dropEnd($this->size);
        $assert->same(
            $result->length(),
            \max(
                0,
                $systemUnderTest->length() - $this->size,
            ),
        );
        $assert->true(
            $systemUnderTest->startsWith($result->toString()),
        );

        return $result;
    }
}
