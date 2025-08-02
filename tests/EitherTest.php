<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\Either;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class EitherTest extends TestCase
{
    use BlackBox;

    public function testMatchLeft(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
            )
            ->prove(function($left, $right) {
                $either = Either::left($left);

                $this->assertSame(
                    $left,
                    $either->match(
                        static fn($value) => $right,
                        function($value) use ($left) {
                            $this->assertSame($left, $value);

                            return $value;
                        },
                    ),
                );
            });
    }

    public function testMatchRight(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
            )
            ->prove(function($left, $right) {
                $either = Either::right($right);

                $this->assertSame(
                    $right,
                    $either->match(
                        function($value) use ($right) {
                            $this->assertSame($right, $value);

                            return $value;
                        },
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testLeftValueIsNotMapped(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::type())
            ->prove(function($initial) {
                $either = Either::left($initial)->map(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $initial,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testRightValueIsMapped(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
            )
            ->prove(function($initial, $mapped) {
                $either = Either::right($initial)->map(function($value) use ($initial, $mapped) {
                    $this->assertSame($initial, $value);

                    return $mapped;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $mapped,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testLeftValueIsNotFlatMapped(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::type())
            ->prove(function($left) {
                $either = Either::left($left)->flatMap(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $left,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testRightValueIsFlatMapped(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::either(
                    Set::type()->map(
                        static fn($value) => Either::left($value),
                    ),
                    Set::type()->map(
                        static fn($value) => Either::right($value),
                    ),
                ),
            )
            ->prove(function($right, $expected) {
                $either = Either::right($right)->flatMap(function($value) use ($right, $expected) {
                    $this->assertSame($right, $value);

                    return $expected;
                });

                $this->assertSame($expected, $either);
            });
    }

    public function testOtherwiseIsCalledWhenLeftValue(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::either(
                    Set::type()->map(
                        static fn($value) => Either::left($value),
                    ),
                    Set::type()->map(
                        static fn($value) => Either::right($value),
                    ),
                ),
            )
            ->prove(function($left, $expected) {
                $either = Either::left($left)->otherwise(static fn() => $expected);

                $this->assertSame($expected, $either);
            });
    }

    public function testOtherwiseIsNotCalledWhenRightValue(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
            )
            ->prove(function($right, $left) {
                $either = Either::right($right)->otherwise(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $right,
                    $either->match(
                        static fn($value) => $value,
                        static fn() => $left,
                    ),
                );
            });
    }

    public function testKeepSameValueWhenFilteringLeftValue(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
                Set::of(true, false),
                Set::type(),
            )
            ->prove(function($left, $right, $predicate, $otherwise) {
                $either = Either::left($left)->filter(
                    static fn() => $predicate,
                    static fn() => $otherwise,
                );

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $left,
                    $either->match(
                        static fn() => $right,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testKeepSameValueWhenRightValueMatchPredicate(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
                Set::type(),
            )
            ->prove(function($right, $left, $otherwise) {
                $either = Either::right($right)->filter(
                    function($value) use ($right) {
                        $this->assertSame($right, $value);

                        return true;
                    },
                    static fn() => $otherwise,
                );

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $right,
                    $either->match(
                        static fn($value) => $value,
                        static fn() => $left,
                    ),
                );
            });
    }

    public function testUseOtherValueWhenRightValueDoesntMatchPredicate(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
                Set::type(),
            )
            ->prove(function($right, $unwanted, $otherwise) {
                $either = Either::right($right)->filter(
                    function($value) use ($right) {
                        $this->assertSame($right, $value);

                        return false;
                    },
                    static fn() => $otherwise,
                );

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $otherwise,
                    $either->match(
                        static fn($value) => $unwanted,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testRightValueIsNotLeftMapped(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::type())
            ->prove(function($initial) {
                $either = Either::right($initial)->leftMap(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $initial,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testLeftValueIsLeftMapped(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
            )
            ->prove(function($initial, $mapped) {
                $either = Either::left($initial)->leftMap(function($value) use ($initial, $mapped) {
                    $this->assertSame($initial, $value);

                    return $mapped;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $mapped,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testMaybe(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
            )
            ->prove(function($left, $right) {
                $this->assertSame(
                    $right,
                    Either::right($right)->maybe()->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
                $this->assertNull(
                    Either::left($left)->maybe()->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
            });
    }

    public function testDefer()
    {
        $loaded = 0;
        $maybe = Either::defer(static function() use (&$loaded) {
            ++$loaded;

            return Either::right('foo');
        })
            ->map(static fn() => 'bar')
            ->flatMap(static fn() => Either::right('baz'))
            ->filter(
                static fn() => false,
                static fn() => 'unwanted',
            )
            ->leftMap(static fn() => 'unwanted bis')
            ->otherwise(static fn() => Either::right('foobar'))
            ->maybe();

        $this->assertSame(0, $loaded);
        $this->assertSame(
            'foobar',
            $maybe->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(1, $loaded);
        $this->assertSame(
            'foobar',
            $maybe->match(
                static fn($value) => $value,
                static fn() => null,
            ),
        );
        $this->assertSame(1, $loaded);
    }

    public function testMemoize(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::type())
            ->prove(function($value) {
                $this->assertEquals(
                    Either::right($value),
                    Either::right($value)->memoize(),
                );
                $this->assertEquals(
                    Either::left($value),
                    Either::left($value)->memoize(),
                );

                $either = Either::defer(static function() use ($value) {
                    return Either::right($value);
                });
                $this->assertSame(
                    $value,
                    $either->memoize()->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
                $this->assertSame(
                    $either->memoize(),
                    $either->memoize(),
                );

                $either = Either::defer(static function() use ($value) {
                    return Either::left($value);
                });
                $this->assertSame(
                    $value,
                    $either->memoize()->match(
                        static fn() => null,
                        static fn($value) => $value,
                    ),
                );
                $this->assertSame(
                    $either->memoize(),
                    $either->memoize(),
                );
            });
    }

    public function testFlip(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::type())
            ->prove(function($data) {
                $left = Either::left($data);

                $this->assertSame(
                    $data,
                    $left
                        ->flip()
                        ->match(
                            static fn($data) => $data,
                            static fn() => null,
                        ),
                );

                $right = Either::right($data);

                $this->assertSame(
                    $data,
                    $right
                        ->flip()
                        ->match(
                            static fn() => null,
                            static fn($data) => $data,
                        ),
                );

                $left = Either::defer(static fn() => Either::left($data));

                $this->assertSame(
                    $data,
                    $left
                        ->flip()
                        ->match(
                            static fn($data) => $data,
                            static fn() => null,
                        ),
                );

                $right = Either::defer(static fn() => Either::right($data));

                $this->assertSame(
                    $data,
                    $right
                        ->flip()
                        ->match(
                            static fn() => null,
                            static fn($data) => $data,
                        ),
                );
            });
    }

    public function testEitherWay(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
            )
            ->prove(function($initial, $new) {
                $this->assertSame(
                    $new,
                    Either::left($initial)
                        ->eitherWay(
                            static fn() => Either::left($initial),
                            function($value) use ($initial, $new) {
                                $this->assertSame($initial, $value);

                                return Either::right($new);
                            },
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
                $this->assertSame(
                    $new,
                    Either::defer(static fn() => Either::left($initial))
                        ->eitherWay(
                            static fn() => Either::left($initial),
                            function($value) use ($initial, $new) {
                                $this->assertSame($initial, $value);

                                return Either::right($new);
                            },
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
                $this->assertSame(
                    $new,
                    Either::right($initial)
                        ->eitherWay(
                            function($value) use ($initial, $new) {
                                $this->assertSame($initial, $value);

                                return Either::right($new);
                            },
                            static fn() => Either::left($initial),
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
                $this->assertSame(
                    $new,
                    Either::defer(static fn() => Either::right($initial))
                        ->eitherWay(
                            function($value) use ($initial, $new) {
                                $this->assertSame($initial, $value);

                                return Either::right($new);
                            },
                            static fn() => Either::left($initial),
                        )
                        ->match(
                            static fn($value) => $value,
                            static fn() => null,
                        ),
                );
            });
    }
}
