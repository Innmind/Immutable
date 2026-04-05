<?php
declare(strict_types = 1);

namespace Properties\Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Sequence,
    Sequence\Union\Left,
    Sequence\Union\Right,
    Sequence\Union\Both,
    Predicate\Instance,
};
use Innmind\BlackBox\{
    Set,
    Property,
    Runner\Assert,
};

/**
 * @implements Property<Sequence>
 */
final class Union implements Property
{
    private function __construct(
        private Sequence $other,
    ) {
    }

    public static function any(): Set
    {
        return Set::sequence(Set::type())
            ->map(static fn($values) => Sequence::of(...$values))
            ->map(static fn($other) => new self($other));
    }

    public function applicableTo(object $systemUnderTest): bool
    {
        return true;
    }

    public function ensureHeldBy(Assert $assert, object $systemUnderTest): object
    {
        $union = $systemUnderTest->union($this->other);

        $assert->same(
            \max($systemUnderTest->size(), $this->other->size()),
            $union->size(),
        );
        $assert->same(
            $systemUnderTest->toList(),
            $union
                ->keep(
                    Instance::of(Left::class)->or(Instance::of(Both::class)),
                )
                ->map(static fn($pair) => $pair->left())
                ->toList(),
        );
        $assert->same(
            $this->other->toList(),
            $union
                ->keep(
                    Instance::of(Right::class)->or(Instance::of(Both::class)),
                )
                ->map(static fn($pair) => $pair->right())
                ->toList(),
        );

        // snap to avoid creating new instances of Left|Right|Both
        return $union->snap();
    }
}
