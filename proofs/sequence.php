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
        'Sequende::defer() holds intermediary values even when no longer used',
        given(
            Set\Sequence::of(Set\Type::any()),
            Set\Sequence::of(Set\Type::any()),
        ),
        static function($assert, $prefix, $suffix) {
            $initial = Sequence::defer((static function() use ($prefix, $suffix) {
                foreach ($prefix as $value) {
                    yield $value;
                }

                foreach ($suffix as $value) {
                    yield $value;
                }
            })());

            // This does a partial read on the generator
            $assert->same(
                $prefix,
                $initial
                    ->take(\count($prefix))
                    ->toList(),
            );

            // The maps are only here to wrap the generator, it doesn't change
            // the values
            $another = $initial
                ->map(static fn($value) => [$value])
                ->map(static fn($values) => $values[0]);
            unset($initial);

            // If it didn't store the intermediary values the array would miss
            // the prefix values due to the partial read on the initial
            // generator due to the ->take()->toList() call above
            $assert->same(
                [...$prefix, ...$suffix],
                $another->toList(),
            );
        },
    );

    yield proof(
        "Sequence::defer() stack trace doesn't show intermediary sequences when not used",
        given(Set\Integers::between(1, 10)),
        static function($assert, $calls) {
            $expected = null;
            $sequence = Sequence::defer((static function() use (&$expected) {
                yield null;

                throw $expected = new Exception;
            })());

            for ($i = 0; $i < $calls; $i++) {
                $sequence = $sequence->map(static fn($value) => $value);
            }

            try {
                $sequence->toList();
                $assert->fail('it should throw');
            } catch (Exception $e) {
                $assert->same($expected, $e);

                $accumulations = \array_filter(
                    $e->getTrace(),
                    static fn($frame) => \str_ends_with($frame['file'] ?? '', 'src/Accumulate.php'),
                );

                $assert->count(1, $accumulations);
            }
        },
    );
};
