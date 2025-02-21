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

    yield test(
        'Set defer nesting calls',
        static function($assert) {
            $set = Set::defer((static function() {
                yield 1;
                yield 2;
                yield 3;
            })());

            $assert->same(
                [
                    [1, 1],
                    [1, 2],
                    [1, 3],
                    [2, 1],
                    [2, 2],
                    [2, 3],
                    [3, 1],
                    [3, 2],
                    [3, 3],
                ],
                $set
                    ->flatMap(static fn($i) => $set->map(
                        static fn($j) => [$i, $j],
                    ))
                    ->toList(),
            );
        },
    );

    yield test(
        'Set defer partial nesting calls',
        static function($assert) {
            $set = Set::defer((static function() {
                yield 1;
                yield 2;
                yield 3;
            })());

            $assert->same(
                [
                    [1, 1],
                    [2, 1],
                    [3, 1],
                ],
                $set
                    ->flatMap(
                        static fn($i) => $set
                            ->find(static fn() => true)
                            ->toSequence()
                            ->toSet()
                            ->map(static fn($j) => [$i, $j]),
                    )
                    ->toList(),
            );
        },
    );
};
