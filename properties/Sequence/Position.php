<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Sequence;

use Innmind\Immutable\Sequence;
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Sequence>
 */
final class Position implements Property
{
    public static function any(): Set
    {
        return Set::of(new self);
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $positionned = $systemUnderTest->via(Sequence\Position::tag(...));

        $assert->true(
            $positionned
                ->first()
                ->match(
                    static fn($position) => $position->first(),
                    static fn() => true,
                ),
        );
        $assert->true(
            $positionned
                ->last()
                ->match(
                    static fn($position) => $position->last(),
                    static fn() => true,
                ),
        );
        $assert->same(
            $systemUnderTest
                ->first()
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            $positionned
                ->first()
                ->match(
                    static fn($position) => $position->unwrap(),
                    static fn() => null,
                ),
        );
        $assert->same(
            $systemUnderTest
                ->last()
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            $positionned
                ->last()
                ->match(
                    static fn($position) => $position->unwrap(),
                    static fn() => null,
                ),
        );
        $_ = $positionned
            ->exclude(static fn($value) => $value->first() || $value->last())
            ->map(static fn($value) => $value->position())
            ->foreach(static fn($position) => $assert->same(
                Sequence\Position::middle,
                $position,
            ));
        $assert->true(
            $positionned
                ->exclude(static fn($value) => $value->first() || $value->last())
                ->map(static fn($value) => $value->unwrap())
                ->equals(
                    $systemUnderTest
                        ->drop(1)
                        ->dropEnd(1),
                ),
        );
        $_ = $systemUnderTest
            ->map(Sequence::of(...))
            ->flatMap(static fn($sequence) => $sequence->via(Sequence\Position::tag(...)))
            ->map(static fn($value) => $value->position())
            ->foreach(static fn($position) => $assert->same(
                Sequence\Position::only,
                $position,
            ));

        return $systemUnderTest;
    }
}
