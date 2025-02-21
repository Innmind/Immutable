<?php

use Innmind\Immutable\Accumulate;
use Innmind\BlackBox\Set;

return static function() {
    yield proof(
        "Accumulate partial access doesn't skip values",
        given(
            Set\Sequence::of(Set\Type::any()),
            Set\Sequence::of(Set\Type::any()),
        ),
        static function($assert, $prefix, $suffix) {
            $accumulate = new Accumulate((static function() use ($prefix, $suffix) {
                yield from $prefix;
                yield from $suffix;
            })());

            foreach ($accumulate as $i => $_) {
                if ($i === (\count($prefix) - 1)) {
                    break;
                }
            }

            $all = [];

            foreach ($accumulate as $value) {
                $all[] = $value;
            }

            $assert->same(
                [...$prefix, ...$suffix],
                $all,
            );
        },
    );

    yield test(
        'Accumuluate nested iteration',
        static function($assert) {
            $accumulate = new Accumulate((static function() {
                yield 1;
                yield 2;
                yield 3;
            })());

            $pairs = [];

            foreach ($accumulate as $i) {
                foreach ($accumulate as $j) {
                    $pairs[] = [$i, $j];
                }
            }

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
                $pairs,
            );
        },
    );
};
