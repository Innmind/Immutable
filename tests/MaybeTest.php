<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Maybe,
    Either,
    Predicate,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class MaybeTest extends TestCase
{
    use BlackBox;

    public function testMapIsNotCalledWhenNoValue()
    {
        $maybe = Maybe::nothing();

        $this->assertInstanceOf(Maybe::class, $maybe->map(static function() {
            throw new \Exception;
        }));
    }

    public function testMaybeNothingMatch()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($unwanted, $nothing) {
                $this->assertSame(
                    $nothing,
                    Maybe::nothing()->match(
                        static fn() => $unwanted,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMaybeOfNullReturnsANothingImplementation()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($unwanted, $nothing) {
                $this->assertSame(
                    $nothing,
                    Maybe::of(null)->match(
                        static fn() => $unwanted,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMaybeJustMatch()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $just, $nothing) {
                $this->assertSame(
                    $just,
                    Maybe::just($initial)->match(
                        function($value) use ($initial, $just) {
                            $this->assertSame($initial, $value);

                            return $just;
                        },
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMaybeOfValueReturnsAJustImplementation()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $just, $nothing) {
                $this->assertSame(
                    $just,
                    Maybe::of($initial)->match(
                        static fn() => $just,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMapIsCalledWhenThereIsAValue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $mapped, $unwanted) {
                $maybe = Maybe::just($initial);
                $maybe2 = $maybe->map(function($value) use ($initial, $mapped) {
                    $this->assertSame($initial, $value);

                    return $mapped;
                });

                $this->assertInstanceOf(Maybe::class, $maybe2);
                $this->assertNotSame($maybe, $maybe2);
                $this->assertSame(
                    $mapped,
                    $maybe2->match(
                        static fn($value) => $value,
                        static fn() => $unwanted,
                    ),
                );
            });
    }

    public function testFlatMapIsNotCalledWhenNoValue()
    {
        $this->assertInstanceOf(Maybe::class, Maybe::nothing()->flatMap(static function() {
            throw new \Exception;
        }));
    }

    public function testFlatMapIsCalledWhenThereIsAValue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $mapped, $nothing) {
                $expected = Maybe::just($mapped);
                $maybe = Maybe::just($initial)->flatMap(function($value) use ($initial, $expected) {
                    $this->assertSame($initial, $value);

                    return $expected;
                });

                $this->assertSame($expected, $maybe);
                $this->assertSame(
                    $mapped,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testOtherwiseIsCalledWhenNoValue()
    {
        $this
            ->forAll(Set\Type::any())
            ->then(function($value) {
                $expected = Maybe::of($value);

                $this->assertSame(
                    $expected,
                    Maybe::nothing()->otherwise(static fn() => $expected),
                );
            });
    }

    public function testOtherwiseIsNotCalledWhenThereIsAValue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial);
                $maybe2 = $maybe->otherwise(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Maybe::class, $maybe2);
                $this->assertNotSame($maybe, $maybe2);
                $this->assertSame(
                    $initial,
                    $maybe2->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testFilterPredicateIsNotCalledWhenNoValue()
    {
        $this->assertInstanceOf(Maybe::class, Maybe::nothing()->filter(static function() {
            throw new \Exception;
        }));
    }

    public function testExcludePredicateIsNotCalledWhenNoValue()
    {
        $this->assertInstanceOf(Maybe::class, Maybe::nothing()->exclude(static function() {
            throw new \Exception;
        }));
    }

    public function testReturnItselfWhenFilterPredicateReturnsTrue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial)->filter(function($value) use ($initial) {
                    $this->assertSame($initial, $value);

                    return true;
                });

                $this->assertInstanceOf(Maybe::class, $maybe);
                $this->assertSame(
                    $initial,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testReturnItselfWhenExcludePredicateReturnsFalse()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial)->exclude(function($value) use ($initial) {
                    $this->assertSame($initial, $value);

                    return false;
                });

                $this->assertInstanceOf(Maybe::class, $maybe);
                $this->assertSame(
                    $initial,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testReturnsANothingWhenFilterPredicateReturnsFalse()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial)->filter(function($value) use ($initial) {
                    $this->assertSame($initial, $value);

                    return false;
                });

                $this->assertInstanceOf(Maybe::class, $maybe);
                $this->assertSame(
                    $nothing,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testReturnsANothingWhenExcludePredicateReturnsTrue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial)->exclude(function($value) use ($initial) {
                    $this->assertSame($initial, $value);

                    return true;
                });

                $this->assertInstanceOf(Maybe::class, $maybe);
                $this->assertSame(
                    $nothing,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testAllMapKeepValuesOrder()
    {
        $this
            ->forAll(
                Set\Sequence::of($this->value())->between(1, 5),
            )
            ->then(function($expected) {
                $maybes = \array_map(
                    static fn($value) => Maybe::just($value),
                    $expected,
                );

                $comprehension = Maybe::all(...$maybes);

                $this->assertInstanceOf(Maybe\Comprehension::class, $comprehension);
                $maybe = $comprehension->map(function(...$args) use ($expected) {
                    $this->assertSame($expected, $args);

                    return $args[0];
                });
                $this->assertInstanceOf(Maybe::class, $maybe);
            });
    }

    public function testAllFlatMapKeepValuesOrder()
    {
        $this
            ->forAll(
                Set\Sequence::of($this->value())->between(1, 5),
            )
            ->then(function($expected) {
                $maybes = \array_map(
                    static fn($value) => Maybe::just($value),
                    $expected,
                );

                $comprehension = Maybe::all(...$maybes);

                $this->assertInstanceOf(Maybe\Comprehension::class, $comprehension);
                $maybe = $comprehension->flatMap(function(...$args) use ($expected) {
                    $this->assertSame($expected, $args);

                    return Maybe::just($args[0]);
                });
                $this->assertInstanceOf(Maybe::class, $maybe);
            });
    }

    public function testAllMapResult()
    {
        $this
            ->forAll(
                Set\Sequence::of(
                    Set\Decorate::immutable(
                        static fn($value) => Maybe::just($value),
                        $this->value(),
                    ),
                )->between(1, 5),
                $this->value(),
            )
            ->then(function($maybes, $expected) {
                $comprehension = Maybe::all(...$maybes);

                $this->assertInstanceOf(Maybe\Comprehension::class, $comprehension);
                $maybe = $comprehension->map(static fn(...$args) => $expected);
                $this->assertSame($expected, $maybe->match(
                    static fn($value) => $value,
                    static fn() => null,
                ));
            });
    }

    public function testAllFlatMapResult()
    {
        $this
            ->forAll(
                Set\Sequence::of(
                    Set\Decorate::immutable(
                        static fn($value) => Maybe::just($value),
                        $this->value(),
                    ),
                )->between(1, 5),
                Set\Either::any(
                    Set\Elements::of(Maybe::nothing()),
                    Set\Decorate::immutable(
                        static fn($value) => Maybe::just($value),
                        $this->value(),
                    ),
                ),
            )
            ->then(function($maybes, $expected) {
                $comprehension = Maybe::all(...$maybes);

                $this->assertInstanceOf(Maybe\Comprehension::class, $comprehension);
                $maybe = $comprehension->flatMap(static fn(...$args) => $expected);
                $this->assertSame($expected, $maybe);
            });
    }

    public function testAllMapNotCalledWhenOneNothingIsPresent()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($value) => Maybe::just($value),
                    $this->value(),
                ),
            )->between(1, 5))
            ->then(function($maybes) {
                $maybes[] = Maybe::nothing();
                $comprehension = Maybe::all(...$maybes);

                $this->assertInstanceOf(Maybe\Comprehension::class, $comprehension);
                $called = false;
                $comprehension->map(static function(...$args) use (&$called) {
                    $called = true;

                    return $args[0];
                });
                $this->assertFalse($called);
            });
    }

    public function testAllFlatMapNotCalledWhenOneNothingIsPresent()
    {
        $this
            ->forAll(Set\Sequence::of(
                Set\Decorate::immutable(
                    static fn($value) => Maybe::just($value),
                    $this->value(),
                ),
            )->between(1, 5))
            ->then(function($maybes) {
                $maybes[] = Maybe::nothing();
                $comprehension = Maybe::all(...$maybes);

                $this->assertInstanceOf(Maybe\Comprehension::class, $comprehension);
                $called = false;
                $comprehension->flatMap(static function(...$args) use (&$called) {
                    $called = true;

                    return Maybe::just($args[0]);
                });
                $this->assertFalse($called);
            });
    }

    public function testEither()
    {
        $this
            ->forAll(Set\Type::any())
            ->then(function($value) {
                $this->assertInstanceOf(Either::class, Maybe::just($value)->either());
                $this->assertInstanceOf(Either::class, Maybe::nothing()->either());
                $this->assertSame(
                    $value,
                    Maybe::just($value)->either()->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
                $this->assertNull(Maybe::nothing()->either()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ));
            });
    }

    public function testKeep()
    {
        $this
            ->forAll(Set\Type::any())
            ->then(function($value) {
                $this->assertNull(
                    Maybe::just($value)
                        ->keep(Predicate\Instance::of(self::class))
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
            });
        $this->assertSame(
            $this,
            Maybe::just($this)
                ->keep(Predicate\Instance::of(self::class))
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );
        $this->assertNull(
            Maybe::nothing()
                ->keep(Predicate\Instance::of(self::class))
                ->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
        );
    }

    public function testDefer()
    {
        $loaded = 0;
        $either = Maybe::defer(static function() use (&$loaded) {
            ++$loaded;

            return Maybe::just('foo');
        })
            ->map(static fn() => 'bar')
            ->flatMap(static fn() => Maybe::just('baz'))
            ->filter(static fn() => false)
            ->otherwise(static fn() => Maybe::just('foobar'))
            ->either();

        $this->assertSame(0, $loaded);
        $this->assertSame(
            'foobar',
            $either->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(1, $loaded);
        $this->assertSame(
            'foobar',
            $either->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(1, $loaded);
    }

    public function testMemoize()
    {
        $this
            ->forAll(Set\Type::any())
            ->then(function($value) {
                $this->assertEquals(
                    Maybe::just($value),
                    Maybe::just($value)->memoize(),
                );

                $maybe = Maybe::defer(static function() use ($value) {
                    return Maybe::just($value);
                });
                $this->assertSame(
                    $value,
                    $maybe->memoize()->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
            });

        $this->assertEquals(
            Maybe::nothing(),
            Maybe::nothing()->memoize(),
        );

        $this
            ->forAll(Set\Either::any(
                Set\Type::any(),
                Set\Elements::of(null),
            ))
            ->then(function($value) {
                $maybe = Maybe::defer(static function() use ($value) {
                    return Maybe::of($value);
                });

                $this->assertSame(
                    $maybe->memoize(),
                    $maybe->memoize(),
                );
            });
    }

    public function testToSequence()
    {
        $this->assertCount(0, Maybe::nothing()->toSequence());
        $this->assertCount(0, Maybe::defer(static fn() => Maybe::nothing())->toSequence());

        $this
            ->forAll($this->value())
            ->then(function($data) {
                $this->assertSame(
                    [$data],
                    Maybe::just($data)->toSequence()->toList(),
                );
                $this->assertSame(
                    [$data],
                    Maybe::defer(static fn() => Maybe::just($data))->toSequence()->toList(),
                );
            });
    }

    public function testEitherWay()
    {
        $this
            ->forAll($this->value())
            ->then(function($data) {
                $this->assertSame(
                    $data,
                    Maybe::nothing()
                        ->eitherWay(
                            static fn() => Maybe::nothing(),
                            static fn() => Maybe::just($data),
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
                $this->assertSame(
                    $data,
                    Maybe::defer(static fn() => Maybe::nothing())
                        ->eitherWay(
                            static fn() => Maybe::nothing(),
                            static fn() => Maybe::just($data),
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
            });
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $new) {
                $this->assertSame(
                    $new,
                    Maybe::just($initial)
                        ->eitherWay(
                            function($value) use ($initial, $new) {
                                $this->assertSame($initial, $value);

                                return Maybe::just($new);
                            },
                            static fn() => Maybe::nothing(),
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
                $this->assertSame(
                    $new,
                    Maybe::defer(static fn() => Maybe::just($initial))
                        ->eitherWay(
                            function($value) use ($initial, $new) {
                                $this->assertSame($initial, $value);

                                return Maybe::just($new);
                            },
                            static fn() => Maybe::nothing(),
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
            });
    }

    private function value(): Set
    {
        return Set\Type::any()->filter(static fn($value) => $value !== null);
    }
}
