<?php
declare(strict_types = 1);

use Innmind\Immutable\Sequence;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        'Sequence::toIdentity()',
        given(Set\Sequence::of(Set\Type::any())),
        static function($assert, $values) {
            $sequence = Sequence::of(...$values);

            $assert->same(
                $sequence->toList(),
                $sequence
                    ->toIdentity()
                    ->unwrap()
                    ->toList(),
            );
        },
    );

    yield proof(
        'Sequence::prepend()',
        given(
            Set\Sequence::of(Set\Type::any()),
            Set\Sequence::of(Set\Type::any()),
        ),
        static function($assert, $first, $second) {
            $assert->same(
                [...$first, ...$second],
                Sequence::of(...$second)
                    ->prepend(Sequence::of(...$first))
                    ->toList(),
            );

            $assert->same(
                [...$first, ...$second],
                Sequence::defer((static function() use ($second) {
                    yield from $second;
                })())
                    ->prepend(Sequence::defer((static function() use ($first) {
                        yield from $first;
                    })()))
                    ->toList(),
            );

            $assert->same(
                [...$first, ...$second],
                Sequence::lazy(static function() use ($second) {
                    yield from $second;
                })
                    ->prepend(Sequence::lazy(static function() use ($first) {
                        yield from $first;
                    }))
                    ->toList(),
            );
        },
    );
};
