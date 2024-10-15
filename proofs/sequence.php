<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Sequence,
    Str,
    Monoid\Concat,
};
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

    yield proof(
        'Sequence::chunk()',
        given(
            Set\Strings::atLeast(100),
            Set\Integers::between(1, 50),
        ),
        static function($assert, $string, $chunk) {
            $chunks = Str::of($string, Str\Encoding::ascii)
                ->split()
                ->chunk($chunk);

            $chunks->foreach(
                static fn($chars) => $assert
                    ->number($chars->size())
                    ->lessThanOrEqual($chunk),
            );
            $chunks
                ->dropEnd(1)
                ->foreach(
                    static fn($chars) => $assert->same(
                        $chunk,
                        $chars->size(),
                    ),
                );

            $assert->same(
                $string,
                $chunks
                    ->flatMap(static fn($chars) => $chars)
                    ->fold(new Concat)
                    ->toString(),
            );
        },
    );

    yield proof(
        'Sequence::windows()',
        given(
            Set\Strings::atLeast(100),
            Set\Integers::between(1, 50),
        ),
        static function($assert, $string, int $size) {
            $chars = Str::of($string, Str\Encoding::ascii)
                ->split();

            $windows = $chars
                ->windows($size);

            $windows->foreach(
                static fn($chars) => $assert
                    ->same(
                        $size,
                        $chars->size()
                    ),
            );

            if ($chars->size() < $size) {
                $assert->same(
                    0,
                    $windows->count()
                );
            } else {
                $assert->same(
                    $chars->count() - $size + 1,
                    $windows->count(),
                );
            }
        },
    );
};
