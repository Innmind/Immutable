<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Set,
    Sequence,
};
use Innmind\BlackBox\Set as DataSet;

return static function() {
    yield proof(
        'Set::match()',
        given(
            DataSet\Type::any(),
            DataSet\Sequence::of(DataSet\Type::any())
                ->map(static fn($values) => Sequence::of(...$values)->distinct()->toList()),
        )->filter(static fn($first, $rest) => !\in_array($first, $rest, true)),
        static function($assert, $first, $rest) {
            $assert->same(
                $first,
                Set::of()->match(
                    static fn() => false,
                    static fn() => $first,
                ),
            );

            $packed = Set::of($first, ...$rest)->match(
                static fn($first, $rest) => [$first, $rest],
                static fn() => null,
            );

            $assert->not()->null($packed);
            $assert->same($first, $packed[0]);
            $assert->same($rest, $packed[1]->toList());
        },
    );

    yield proof(
        'Set::unsorted()',
        given(
            DataSet\Sequence::of(DataSet\Type::any()),
        ),
        static function($assert, $values) {
            $set = Set::of(...$values);
            $sequence = $set->unsorted();

            $assert->true(
                $sequence->matches($set->contains(...)),
            );
            $assert->true(
                $set->matches($sequence->contains(...)),
            );
        },
    );
};
